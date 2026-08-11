<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MakeStaffUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_linked_dentist(): void
    {
        $this->mockSuccessfulProvisioning();

        $this->artisan('app:make-user', ['--role' => 'dentist'])
            ->expectsQuestion('Full name', 'Dr. Dental User')
            ->expectsQuestion('Email address', 'dentist@example.test')
            ->expectsQuestion('Phone number (optional)', '09171234567')
            ->expectsQuestion('Dentist license number (optional)', 'DEN-1234')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'dentist@example.test',
            'supabase_uid' => '123e4567-e89b-12d3-a456-426614174000',
            'password' => null,
            'role' => 'dentist',
            'license_no' => 'DEN-1234',
            'status' => 'active',
        ]);
    }

    public function test_it_creates_a_linked_receptionist(): void
    {
        $this->mockSuccessfulProvisioning();

        $this->artisan('app:make-user', ['--role' => 'receptionist'])
            ->expectsQuestion('Full name', 'Reception User')
            ->expectsQuestion('Email address', 'reception@example.test')
            ->expectsQuestion('Phone number (optional)', '')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'reception@example.test',
            'role' => 'receptionist',
            'license_no' => null,
            'status' => 'active',
        ]);
    }

    public function test_it_rejects_an_invalid_role_before_provisioning(): void
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldNotReceive('adminCreateUser');
        $this->app->instance(SupabaseAuth::class, $supabase);

        $this->artisan('app:make-user', ['--role' => 'admin'])
            ->expectsQuestion('Full name', 'Invalid User')
            ->expectsQuestion('Email address', 'invalid@example.test')
            ->expectsQuestion('Phone number (optional)', '')
            ->assertFailed();
    }

    public function test_it_removes_auth_user_when_local_creation_fails(): void
    {
        $supabase = $this->mockSuccessfulProvisioning();
        $supabase->shouldReceive('adminDeleteUser')->once()->andReturnTrue();
        User::creating(fn () => throw new RuntimeException('Local failure'));

        $this->artisan('app:make-user', ['--role' => 'receptionist'])
            ->expectsQuestion('Full name', 'Reception User')
            ->expectsQuestion('Email address', 'reception@example.test')
            ->expectsQuestion('Phone number (optional)', '')
            ->assertFailed();
    }

    private function mockSuccessfulProvisioning(): \Mockery\MockInterface
    {
        $supabase = Mockery::mock(SupabaseAuth::class);
        $supabase->shouldReceive('adminFindByEmail')->once()->andReturnNull();
        $supabase->shouldReceive('adminCreateUser')->once()->andReturn([
            'ok' => true,
            'user' => ['id' => '123e4567-e89b-12d3-a456-426614174000'],
        ]);
        $this->app->instance(SupabaseAuth::class, $supabase);

        return $supabase;
    }
}
