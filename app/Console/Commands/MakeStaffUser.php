<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SupabaseAuth;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class MakeStaffUser extends Command
{
    protected $signature = 'app:make-user {--role= : dentist or receptionist}';

    protected $description = 'Create a Supabase Auth account and linked dentist or receptionist profile';

    public function handle(SupabaseAuth $supabase, TransactionalEmailDispatcher $emails): int
    {
        $role = strtolower((string) ($this->option('role') ?: $this->choice(
            'Role',
            ['dentist', 'receptionist'],
        )));
        $name = $this->ask('Full name');
        $email = $this->ask('Email address');
        $phone = $this->ask('Phone number (optional)');
        $license = $role === 'dentist' ? $this->ask('Dentist license number (optional)') : null;
        $password = Str::password(18, symbols: true);

        $validator = Validator::make(
            compact('role', 'name', 'email', 'phone', 'license', 'password'),
            [
                'role' => ['required', 'in:dentist,receptionist'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['nullable', 'string', 'max:255'],
                'license' => ['nullable', 'string', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($supabase->adminFindByEmail($email)) {
            $this->error('A Supabase Auth account already exists for this email.');

            return self::FAILURE;
        }

        $result = $supabase->adminCreateUser($email, $password);
        if (! $result['ok']) {
            $this->error($result['message'] ?? 'Could not create the Supabase Auth account.');

            return self::FAILURE;
        }

        $uid = $result['user']['id'];

        try {
            $user = DB::transaction(fn () => User::create([
                'name' => $name,
                'email' => $email,
                'supabase_uid' => $uid,
                'password' => null,
                'role' => $role,
                'phone' => filled($phone) ? $phone : null,
                'license_no' => $role === 'dentist' && filled($license) ? $license : null,
                'status' => 'active',
                'must_change_password' => true,
            ]));
        } catch (Throwable $exception) {
            $supabase->adminDeleteUser($uid);
            report($exception);
            $this->error('The local staff profile could not be created. The new Supabase Auth account was removed.');

            return self::FAILURE;
        }

        $emails->dispatch('staff_credentials', $user->email, $user, [
            'temporary_password' => $password,
            'login_url' => route('login'),
        ]);

        $this->info(ucfirst($role)." account created for {$email}. Temporary credentials were queued for email delivery.");

        return self::SUCCESS;
    }
}
