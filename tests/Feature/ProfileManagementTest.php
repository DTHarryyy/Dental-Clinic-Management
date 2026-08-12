<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_a_profile(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_profile_page_displays_the_authenticated_account(): void
    {
        $user = User::factory()->dentist()->create(['name' => 'Maria De La Cruz']);

        $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Maria De La Cruz')
            ->assertSee('Professional license number')
            ->assertSee('Security');
    }

    public function test_personal_information_updates_without_contacting_supabase(): void
    {
        $user = User::factory()->dentist()->create();
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldNotReceive('signIn', 'adminUpdateUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($user)->patch(route('profile.details.update'), [
            'full_name' => 'Maria Lourdes De La Cruz',
            'phone' => '09171234567',
            'license_no' => 'PRC-12345',
        ])->assertRedirect(route('profile.show').'#personal-information');

        $user->refresh();
        $this->assertSame('Maria Lourdes De La Cruz', $user->name);
        $this->assertSame('09171234567', $user->phone);
        $this->assertSame('PRC-12345', $user->license_no);
    }

    public function test_non_dentists_cannot_change_their_license_from_profile(): void
    {
        $user = User::factory()->create(['role' => 'receptionist', 'status' => 'active', 'license_no' => null]);

        $this->actingAs($user)->patch(route('profile.details.update'), [
            'full_name' => 'Reception User',
            'phone' => null,
            'license_no' => 'SHOULD-NOT-SAVE',
        ])->assertRedirect();

        $this->assertNull($user->refresh()->license_no);
    }

    public function test_email_change_requires_current_password(): void
    {
        $user = User::factory()->admin()->create(['email' => 'old@example.test']);

        $this->actingAs($user)->put(route('profile.security.update'), [
            'email' => 'new@example.test',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame('old@example.test', $user->refresh()->email);
    }

    public function test_incorrect_password_leaves_credentials_unchanged(): void
    {
        $user = User::factory()->admin()->create(['email' => 'old@example.test', 'supabase_uid' => 'uid-1']);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->with('old@example.test', 'wrong')->andReturn(['ok' => false]);
        $supabase->shouldNotReceive('adminUpdateUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($user)->put(route('profile.security.update'), [
            'email' => 'new@example.test',
            'current_password' => 'wrong',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame('old@example.test', $user->refresh()->email);
    }

    public function test_email_and_password_are_synchronized_in_one_supabase_update(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'old@example.test',
            'supabase_uid' => 'uid-1',
            'must_change_password' => true,
        ]);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->with('old@example.test', 'OldPassword1')->andReturn(['ok' => true, 'user' => ['id' => 'uid-1']]);
        $supabase->shouldReceive('adminUpdateUser')->once()->with('uid-1', [
            'email' => 'new@example.test',
            'email_confirm' => true,
            'password' => 'NewPassword1',
        ])->andReturn(['ok' => true]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($user)->put(route('profile.security.update'), [
            'email' => 'new@example.test',
            'current_password' => 'OldPassword1',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])->assertRedirect(route('profile.show').'#security');

        $user->refresh();
        $this->assertSame('new@example.test', $user->email);
        $this->assertFalse($user->must_change_password);
    }

    public function test_supabase_failure_does_not_change_the_local_email(): void
    {
        $user = User::factory()->admin()->create(['email' => 'old@example.test', 'supabase_uid' => 'uid-1']);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->andReturn(['ok' => true, 'user' => ['id' => 'uid-1']]);
        $supabase->shouldReceive('adminUpdateUser')->once()->andReturn(['ok' => false, 'message' => 'Email already registered.']);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($user)->put(route('profile.security.update'), [
            'email' => 'new@example.test',
            'current_password' => 'OldPassword1',
        ])->assertSessionHasErrors('email');

        $this->assertSame('old@example.test', $user->refresh()->email);
    }

    public function test_verified_legacy_account_is_relinked_during_security_update(): void
    {
        $user = User::factory()->admin()->create(['email' => 'legacy@example.test', 'supabase_uid' => null]);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('signIn')->once()->andReturn(['ok' => true, 'user' => ['id' => 'recovered-uid']]);
        $supabase->shouldReceive('adminUpdateUser')->once()->with('recovered-uid', ['password' => 'NewPassword1'])->andReturn(['ok' => true]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->actingAs($user)->put(route('profile.security.update'), [
            'email' => 'legacy@example.test',
            'current_password' => 'OldPassword1',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])->assertRedirect();

        $this->assertSame('recovered-uid', $user->refresh()->supabase_uid);
    }
}
