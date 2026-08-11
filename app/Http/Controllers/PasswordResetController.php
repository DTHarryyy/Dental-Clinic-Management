<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseAuth;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, TransactionalEmailDispatcher $emails)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->where('status', 'active')->whereNotNull('supabase_uid')->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(['email' => $user->email], [
                'token' => Hash::make($token), 'created_at' => now(),
            ]);
            $emails->dispatch('password_reset', $user->email, $user, [
                'reset_url' => route('password.reset', ['token' => $token, 'email' => $user->email]),
            ]);
        }

        return back()->with('status', 'If an active account exists for that email, a reset link has been sent.');
    }

    public function edit(string $token, Request $request)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request, SupabaseAuth $supabase)
    {
        $data = $request->validate([
            'email' => ['required', 'email'], 'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();
        $valid = $row && $row->created_at && now()->subMinutes(60)->lte($row->created_at) && Hash::check($data['token'], $row->token);
        $user = $valid ? User::where('email', $data['email'])->where('status', 'active')->first() : null;
        if (! $user?->supabase_uid) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or expired.']);
        }

        $result = $supabase->adminUpdateUser($user->supabase_uid, ['password' => $data['password']]);
        if (! $result['ok']) {
            throw ValidationException::withMessages(['email' => 'The password could not be reset. Please request another link.']);
        }

        DB::transaction(function () use ($user) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->update(['must_change_password' => false]);
        });

        return redirect()->route('login')->with('status', 'Your password has been reset. You may now sign in.');
    }
}
