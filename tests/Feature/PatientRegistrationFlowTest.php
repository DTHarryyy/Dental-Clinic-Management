<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientAccountLinkRequest;
use App\Models\PatientConsent;
use App\Models\PublicSiteSetting;
use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PatientRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_links_exact_active_patient_and_records_consents(): void
    {
        PublicSiteSetting::current()->update([
            'privacy_policy_version' => 'privacy-v2',
            'terms_version' => 'terms-v3',
        ]);
        $patient = Patient::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana@example.test',
            'status' => 'active',
        ]);

        $this->mockSignup('supabase-patient-1');

        $this->post(route('register.store'), $this->registrationPayload([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => ' ANA@example.test ',
        ]))->assertRedirect(route('verify-email'))
            ->assertSessionHas('verification_email', 'ana@example.test');

        $user = User::where('email', 'ana@example.test')->firstOrFail();
        $this->assertSame('patient', $user->role);
        $this->assertSame($patient->id, $user->patient_id);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('patient_consents', [
            'user_id' => $user->id,
            'type' => 'privacy',
            'version' => 'privacy-v2',
        ]);
        $this->assertDatabaseHas('patient_consents', [
            'user_id' => $user->id,
            'type' => 'terms',
            'version' => 'terms-v3',
        ]);
        $this->assertSame(2, PatientConsent::where('user_id', $user->id)->count());
    }

    public function test_registration_creates_patient_when_no_email_match_exists(): void
    {
        $this->mockSignup('supabase-patient-2');

        $this->post(route('register.store'), $this->registrationPayload([
            'first_name' => 'New',
            'last_name' => 'Patient',
            'email' => 'newpatient@example.test',
            'mobile' => '09170000000',
        ]))->assertRedirect(route('verify-email'));

        $user = User::where('email', 'newpatient@example.test')->firstOrFail();
        $this->assertNotNull($user->patient_id);
        $this->assertDatabaseHas('patients', [
            'id' => $user->patient_id,
            'first_name' => 'New',
            'last_name' => 'Patient',
            'mobile' => '09170000000',
            'status' => 'active',
        ]);
    }

    public function test_registration_creates_review_request_for_ambiguous_patient_match(): void
    {
        Patient::factory()->count(2)->create([
            'email' => 'shared@example.test',
            'status' => 'active',
        ]);
        $this->mockSignup('supabase-patient-3');

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'shared@example.test',
        ]))->assertRedirect(route('verify-email'));

        $user = User::where('email', 'shared@example.test')->firstOrFail();
        $this->assertNull($user->patient_id);
        $this->assertDatabaseHas('patient_account_link_requests', [
            'user_id' => $user->id,
            'normalized_email' => 'shared@example.test',
            'candidate_count' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_patient_enters_email_code_to_verify_account(): void
    {
        $user = User::factory()->patient()->unverified()->create([
            'email' => 'verify@example.test',
            'supabase_uid' => 'verify-code-uid',
        ]);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('verifyEmailCode')
            ->once()
            ->with('verify@example.test', '123456', 'email')
            ->andReturn([
                'ok' => true,
                'user' => ['id' => 'verify-code-uid', 'email' => 'verify@example.test'],
            ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('verify-email.consume'), [
            'email' => ' VERIFY@example.test ',
            'code' => '123456',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Email verified. You can now sign in.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_resend_verification_sends_a_new_code_without_enumerating_accounts(): void
    {
        User::factory()->patient()->unverified()->create([
            'email' => 'resend@example.test',
        ]);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('resendVerification')
            ->once()
            ->with('resend@example.test', route('verify-email'))
            ->andReturn(['ok' => true]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('verify-email.resend'), [
            'email' => ' RESEND@example.test ',
        ])->assertRedirect()
            ->assertSessionHas('status', 'If that patient account exists, a new verification code has been sent.')
            ->assertSessionHas('verification_email', 'resend@example.test');
    }

    public function test_receptionist_can_resolve_patient_account_link_request(): void
    {
        $patient = Patient::factory()->create(['status' => 'active']);
        $patientUser = User::factory()->patient()->create([
            'patient_id' => null,
            'email' => 'review@example.test',
        ]);
        $linkRequest = PatientAccountLinkRequest::create([
            'user_id' => $patientUser->id,
            'normalized_email' => 'review@example.test',
            'candidate_count' => 2,
            'status' => 'pending',
        ]);
        $receptionist = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);

        $this->actingAs($receptionist)
            ->patch(route('patient-accounts.resolve', $linkRequest), [
                'decision' => 'approve',
                'patient_id' => $patient->id,
                'note' => 'Verified patient identity at the front desk.',
            ])->assertRedirect();

        $this->assertSame($patient->id, $patientUser->fresh()->patient_id);
        $this->assertDatabaseHas('patient_account_link_requests', [
            'id' => $linkRequest->id,
            'status' => 'resolved',
            'selected_patient_id' => $patient->id,
            'resolved_by_user_id' => $receptionist->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => 'account_link_resolved',
            'notifiable_id' => $patientUser->id,
        ]);
    }

    public function test_local_registration_failure_deletes_new_supabase_account(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signUp')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => 'supabase-cleanup-id'],
        ]);
        $supabase->shouldReceive('adminDeleteUser')->once()->with('supabase-cleanup-id')->andReturnTrue();
        $this->app->instance(SupabaseAuth::class, $supabase);

        User::creating(fn () => throw new RuntimeException('Local write failed'));

        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'cleanup@example.test',
        ]));

        $this->assertDatabaseMissing('users', ['email' => 'cleanup@example.test']);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return [
            'first_name' => 'Patient',
            'last_name' => 'User',
            'mobile' => '09171234567',
            'email' => 'patient@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'privacy_accepted' => '1',
            'terms_accepted' => '1',
            ...$overrides,
        ];
    }

    private function mockSignup(string $uid): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signUp')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => $uid],
        ]);
        $supabase->shouldNotReceive('adminDeleteUser');
        $this->app->instance(SupabaseAuth::class, $supabase);
    }
}
