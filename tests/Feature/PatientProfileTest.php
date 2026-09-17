<?php

namespace Tests\Feature;

use App\Services\SupabaseAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreatesPatientAccounts;
use Tests\TestCase;

class PatientProfileTest extends TestCase
{
    use CreatesPatientAccounts, RefreshDatabase;

    public function test_update_writes_the_validated_fields_and_ignores_anything_else(): void
    {
        [$user, $patient] = $this->linkedPatient('profile-update@example.test');

        $this->actingAs($user)->patch(route('patient.profile.update'), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'mobile' => '09170000000',
            'address' => '123 New Street',
            'email' => 'hijacked@example.test',
            'status' => 'inactive',
        ])->assertRedirect(route('patient.profile'));

        $user->refresh();
        $patient->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('Updated', $patient->first_name);
        $this->assertSame('123 New Street', $patient->address);
        $this->assertSame('profile-update@example.test', $patient->email);
        $this->assertSame('active', $patient->status);
    }

    public function test_security_requires_the_correct_current_password_and_rotates_the_session(): void
    {
        [$user] = $this->linkedPatient('profile-security@example.test', [], ['supabase_uid' => 'uid-security-1']);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->with('profile-security@example.test', 'wrong-password')
            ->andReturn(['ok' => false, 'message' => 'Invalid email or password.']);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($user)->put(route('patient.profile.security'), [
            'current_password' => 'wrong-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_security_updates_the_password_and_rotates_the_session_on_success(): void
    {
        [$user] = $this->linkedPatient('profile-security-ok@example.test', [], ['supabase_uid' => 'uid-security-2']);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->with('profile-security-ok@example.test', 'current-password')
            ->andReturn(['ok' => true, 'user' => ['id' => 'uid-security-2']]);
        $supabase->shouldReceive('adminUpdateUser')->once()->with('uid-security-2', ['password' => 'brand-new-password'])
            ->andReturn(['ok' => true]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($user);
        $tokenBefore = session()->token();

        $this->put(route('patient.profile.security'), [
            'current_password' => 'current-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect();

        $this->assertNotSame($tokenBefore, session()->token());
    }

    public function test_security_is_throttled(): void
    {
        [$user] = $this->linkedPatient('profile-security-throttle@example.test', [], ['supabase_uid' => 'uid-security-3']);

        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->andReturn(['ok' => false, 'message' => 'Invalid email or password.']);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $payload = ['current_password' => 'wrong', 'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->put(route('patient.profile.security'), $payload)->assertSessionHasErrors('current_password');
        }

        $this->actingAs($user)->put(route('patient.profile.security'), $payload)->assertStatus(429);
    }
}
