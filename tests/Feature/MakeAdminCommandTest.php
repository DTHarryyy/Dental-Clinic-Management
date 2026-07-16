<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_account(): void
    {
        $this->artisan('app:make-admin')
            ->expectsQuestion('Full name', 'Dr. Admin')
            ->expectsQuestion('Email address', 'admin@dentalclinic.test')
            ->expectsQuestion('Password (min. 8 characters)', 'SecurePass123')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@dentalclinic.test',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
