<?php

namespace Tests\Feature;

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
}
