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

    public function test_repeat_registration_for_an_unverified_patient_continues_to_verification_without_changing_account_data(): void
    {
        $patient = Patient::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Patient',
            'email' => 'retry@example.test',
            'mobile' => '09170000000',
        ]);
        $user = User::factory()->patient()->unverified()->create([
            'name' => 'Original Patient',
            'email' => 'retry@example.test',
            'phone' => '09170000000',
            'patient_id' => $patient->id,
            'supabase_uid' => 'retry-uid',
        ]);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldNotReceive('signUp');
        $supabase->shouldNotReceive('adminDeleteUser');
        $supabase->shouldReceive('resendVerification')
            ->once()
            ->with('retry@example.test', route('verify-email'))
            ->andReturn(['ok' => true]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('register.store'), $this->registrationPayload([
            'first_name' => 'Changed',
            'last_name' => 'Details',
            'mobile' => '09999999999',
            'email' => ' RETRY@EXAMPLE.TEST ',
            'password' => 'DifferentPass123',
            'password_confirmation' => 'DifferentPass123',
        ]))->assertRedirect(route('verify-email'))
            ->assertSessionHas('verification_email', 'retry@example.test')
            ->assertSessionHas('status', 'Your account was already created. We sent a new 6-digit verification code to your email.');

        $user->refresh();
        $this->assertSame('Original Patient', $user->name);
        $this->assertSame('09170000000', $user->phone);
        $this->assertSame($patient->id, $user->patient_id);
        $this->assertSame('retry-uid', $user->supabase_uid);
        $this->assertSame(1, User::where('email', 'retry@example.test')->count());
        $this->assertSame(0, PatientConsent::where('user_id', $user->id)->count());
    }

    public function test_repeat_registration_still_continues_when_resending_verification_fails(): void
    {
        User::factory()->patient()->unverified()->create(['email' => 'retry-failed@example.test']);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldNotReceive('signUp');
        $supabase->shouldReceive('resendVerification')->once()->andReturn(['ok' => false]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'retry-failed@example.test',
        ]))->assertRedirect(route('verify-email'))
            ->assertSessionHas('status', 'Your account was already created. Enter your existing verification code or request a new one.');
    }

    public function test_repeat_registration_limits_automatic_verification_resends(): void
    {
        User::factory()->patient()->unverified()->create(['email' => 'retry-limited@example.test']);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldNotReceive('signUp');
        $supabase->shouldReceive('resendVerification')->times(3)->andReturn(['ok' => false]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        foreach (range(1, 4) as $attempt) {
            $response = $this->post(route('register.store'), $this->registrationPayload([
                'email' => 'retry-limited@example.test',
            ]));

            $response->assertRedirect(route('verify-email'))
                ->assertSessionHas('status', 'Your account was already created. Enter your existing verification code or request a new one.');
        }
    }

    public function test_verified_staff_and_inactive_accounts_cannot_be_retried_as_patient_registration(): void
    {
        $accounts = [
            ['email' => 'verified@example.test', 'role' => 'patient', 'status' => 'active', 'email_verified_at' => now()],
            ['email' => 'staff@example.test', 'role' => 'receptionist', 'status' => 'active', 'email_verified_at' => now()],
            ['email' => 'inactive@example.test', 'role' => 'patient', 'status' => 'inactive', 'email_verified_at' => null],
        ];
        foreach ($accounts as $account) {
            User::factory()->create($account);
        }

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldNotReceive('signUp');
        $supabase->shouldNotReceive('resendVerification');
        $supabase->shouldNotReceive('adminDeleteUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('register.store'), $this->registrationPayload(['email' => 'verified@example.test']))
            ->assertSessionHasErrors(['email' => 'An account already exists for this email. Sign in or reset your password.']);
        $this->post(route('register.store'), $this->registrationPayload(['email' => 'staff@example.test']))
            ->assertSessionHasErrors(['email' => 'An account already exists for this email. Sign in or reset your password.']);
        $this->post(route('register.store'), $this->registrationPayload(['email' => 'inactive@example.test']))
            ->assertSessionHasErrors(['email' => 'This account is inactive. Contact the clinic for assistance.']);
    }

    public function test_new_registration_normalizes_email_before_creating_account(): void
    {
        $this->mockSignup('normalized-patient-uid');

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => ' NEWPATIENT@EXAMPLE.TEST ',
        ]))->assertRedirect(route('verify-email'));

        $user = User::where('email', 'newpatient@example.test')->firstOrFail();
        $this->assertSame('newpatient@example.test', $user->email);
        $this->assertSame('newpatient@example.test', $user->patient->email);
    }

    public function test_email_unique_race_recovers_to_verification_without_deleting_the_winning_account(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signUp')->once()->andReturnUsing(function (): array {
            User::factory()->patient()->unverified()->create([
                'email' => 'race@example.test',
                'supabase_uid' => 'race-winner-uid',
            ]);

            return ['ok' => true, 'user' => ['id' => 'race-attempt-uid']];
        });
        $supabase->shouldReceive('resendVerification')
            ->once()
            ->with('race@example.test', route('verify-email'))
            ->andReturn(['ok' => true]);
        $supabase->shouldNotReceive('adminDeleteUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'race@example.test',
        ]))->assertRedirect(route('verify-email'))
            ->assertSessionHas('verification_email', 'race@example.test');

        $this->assertSame(1, User::where('email', 'race@example.test')->count());
        $this->assertSame(0, PatientConsent::count());
    }

    public function test_auth_provider_only_duplicate_returns_controlled_account_message(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signUp')->once()->andReturn([
            'ok' => false,
            'code' => 'email_exists',
            'message' => 'User already registered.',
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'orphaned-auth@example.test',
        ]))->assertSessionHasErrors([
            'email' => 'An authentication account already exists for this email. Sign in or reset your password.',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'orphaned-auth@example.test']);
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

    public function test_cleanup_skips_a_supabase_uid_that_is_already_linked_to_a_local_user(): void
    {
        User::factory()->create([
            'email' => 'linked-uid@example.test',
            'supabase_uid' => 'already-linked-uid',
        ]);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signUp')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => 'already-linked-uid'],
        ]);
        $supabase->shouldNotReceive('adminDeleteUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->withoutExceptionHandling();
        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'different-email@example.test',
        ]));
    }

    public function test_registration_page_has_a_duplicate_submission_guard(): void
    {
        // Asserts the guard mechanism (an Alpine flag set on submit, wired to disable the
        // submit button) rather than its exact copy or styling, so a cosmetic edit to the
        // button text or spinner class doesn't break this test.
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<form[^>]*\bx-data="\{[^"]*\bsubmitting:\s*false\b[^"]*\}"[^>]*\bx-on:submit="submitting\s*=\s*true"/',
            $html,
        );

        $this->assertMatchesRegularExpression(
            '/<button[^>]*\btype="submit"[^>]*:disabled="submitting"/',
            $html,
        );
    }

    public function test_registration_rate_limit_returns_to_the_form_with_a_clear_retry_message(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->from(route('register'))->post(route('register.store'), [])
                ->assertRedirect(route('register'));
        }

        $this->from(route('register'))->post(route('register.store'), [])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'email' => 'Too many account-creation attempts. Please wait one minute before trying again.',
            ]);

        $this->get(route('register'))->assertOk();
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
