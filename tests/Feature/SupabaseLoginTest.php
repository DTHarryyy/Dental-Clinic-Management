<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SupabaseLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_resolves_the_local_profile_by_supabase_uid(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'profile@example.test',
            'supabase_uid' => '123e4567-e89b-12d3-a456-426614174000',
        ]);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->andReturn([
            'ok' => true,
            'user' => [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'email' => 'login@example.test',
            ],
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('login.attempt'), [
            'email' => 'login@example.test',
            'password' => 'SecurePass123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_supabase_account_without_linked_profile_is_rejected(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => '123e4567-e89b-12d3-a456-426614174000'],
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('login.attempt'), [
            'email' => 'orphan@example.test',
            'password' => 'SecurePass123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unverified_patient_login_resends_code_and_redirects_to_verification(): void
    {
        User::factory()->patient()->unverified()->create([
            'email' => 'pending@example.test',
            'supabase_uid' => 'pending-login-uid',
        ]);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->with('pending@example.test', 'SecurePass123')->andReturn([
            'ok' => false,
            'code' => 'email_not_confirmed',
            'message' => 'Email not confirmed',
        ]);
        $supabase->shouldReceive('resendVerification')
            ->once()
            ->with('pending@example.test', route('verify-email'))
            ->andReturn(['ok' => true]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('login.attempt'), [
            'email' => ' PENDING@example.test ',
            'password' => 'SecurePass123',
        ])->assertRedirect(route('verify-email'))
            ->assertSessionHas('verification_email', 'pending@example.test')
            ->assertSessionHas('status', 'Your patient account still needs email verification. We sent a new 6-digit code to your email.');

        $this->assertGuest();
    }

    public function test_confirmed_supabase_patient_login_repairs_missing_local_verification(): void
    {
        $patient = Patient::factory()->create(['status' => 'active']);
        $user = User::factory()->patient()->unverified()->create([
            'email' => 'repaired@example.test',
            'patient_id' => $patient->id,
            'supabase_uid' => 'repaired-login-uid',
        ]);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => 'repaired-login-uid'],
        ]);
        $supabase->shouldNotReceive('resendVerification');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->post(route('login.attempt'), [
            'email' => 'repaired@example.test',
            'password' => 'SecurePass123',
        ])->assertRedirect(route('patient.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_patient_login_ignores_staff_intended_urls(): void
    {
        $patient = Patient::factory()->create(['status' => 'active']);
        $user = User::factory()->patient()->create([
            'patient_id' => $patient->id,
            'supabase_uid' => 'patient-login-uid',
        ]);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => 'patient-login-uid'],
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'SecurePass123',
            ])->assertRedirect(route('patient.dashboard'));
    }

    public function test_staff_login_ignores_patient_intended_urls(): void
    {
        $user = User::factory()->admin()->create([
            'supabase_uid' => 'staff-login-uid',
        ]);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => 'staff-login-uid'],
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->withSession(['url.intended' => route('patient.dashboard')])
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'SecurePass123',
            ])->assertRedirect(route('dashboard'));
    }
}
