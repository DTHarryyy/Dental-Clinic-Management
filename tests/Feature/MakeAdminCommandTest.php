<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_matching_supabase_and_local_admin_accounts(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('adminFindByEmail')->once()->andReturnNull();
        $supabase->shouldReceive('adminCreateUser')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => '123e4567-e89b-12d3-a456-426614174000'],
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->runCommand()->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@dentalclinic.test',
            'supabase_uid' => '123e4567-e89b-12d3-a456-426614174000',
            'password' => null,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_it_refuses_an_existing_local_account(): void
    {
        User::factory()->create(['email' => 'admin@dentalclinic.test']);
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldNotReceive('adminCreateUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->runCommand()->assertFailed();
    }

    public function test_it_refuses_an_existing_supabase_account(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('adminFindByEmail')->once()->andReturn(['id' => 'existing']);
        $supabase->shouldNotReceive('adminCreateUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->runCommand()->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'admin@dentalclinic.test']);
    }

    public function test_it_reports_supabase_creation_failure_without_local_write(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('adminFindByEmail')->once()->andReturnNull();
        $supabase->shouldReceive('adminCreateUser')->once()->andReturn(['ok' => false, 'message' => 'Provider unavailable']);
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->runCommand()->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'admin@dentalclinic.test']);
    }

    public function test_it_removes_auth_account_when_local_creation_fails(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('adminFindByEmail')->once()->andReturnNull();
        $supabase->shouldReceive('adminCreateUser')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => '123e4567-e89b-12d3-a456-426614174000'],
        ]);
        $supabase->shouldReceive('adminDeleteUser')->once()->with('123e4567-e89b-12d3-a456-426614174000')->andReturnTrue();
        $this->app->instance(SupabaseAuth::class, $supabase);
        User::creating(fn () => throw new RuntimeException('Local write failed'));

        $this->runCommand()->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'admin@dentalclinic.test']);
    }

    private function runCommand()
    {
        return $this->artisan('app:make-admin')
            ->expectsQuestion('Full name', 'Dr. Admin')
            ->expectsQuestion('Email address', 'admin@dentalclinic.test')
            ->expectsQuestion('Password (min. 8 characters)', 'SecurePass123');
    }
}
