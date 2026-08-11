<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request, SupabaseAuth $supabase)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $result = $supabase->signIn($credentials['email'], $credentials['password']);

        if (! $result['ok']) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $uid = $result['user']['id'] ?? null;
        $user = $uid ? User::where('supabase_uid', $uid)->first() : null;

        if (! $user) {
            return back()->withErrors(['email' => 'No account found for this email. Contact an administrator.'])->onlyInput('email');
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account is inactive. Contact an administrator.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
