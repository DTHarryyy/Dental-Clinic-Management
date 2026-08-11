<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin';

    protected $description = 'Create the first admin account so you can log in to the dashboard';

    public function handle(SupabaseAuth $supabase): int
    {
        $name = $this->ask('Full name');
        $email = $this->ask('Email address');
        $password = $this->secret('Password (min. 8 characters)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($supabase->adminFindByEmail($email)) {
            $this->error('A Supabase Auth account already exists for this email. Link or remove it before retrying.');

            return self::FAILURE;
        }

        $result = $supabase->adminCreateUser($email, $password);
        if (! $result['ok']) {
            $this->error($result['message'] ?? 'Could not create the Supabase Auth account.');

            return self::FAILURE;
        }

        $uid = $result['user']['id'];

        try {
            DB::transaction(fn () => User::create([
                'name' => $name,
                'email' => $email,
                'supabase_uid' => $uid,
                'password' => null,
                'role' => 'admin',
                'status' => 'active',
            ]));
        } catch (Throwable $exception) {
            $supabase->adminDeleteUser($uid);
            report($exception);
            $this->error('The local admin profile could not be created. The new Supabase Auth account was removed.');

            return self::FAILURE;
        }

        $this->info("Admin account created for {$email}. You can now log in at /login.");

        return self::SUCCESS;
    }
}
