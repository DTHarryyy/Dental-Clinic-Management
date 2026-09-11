<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PatientAccountLinker;
use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect($this->homeFor(Auth::user()));
        }

        return view('auth.login');
    }

    public function login(Request $request, SupabaseAuth $supabase, PatientAccountLinker $linker)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = PatientAccountLinker::normalizeEmail($credentials['email']);
        $result = $supabase->signIn($email, $credentials['password']);

        if (! $result['ok']) {
            if ($this->isEmailNotConfirmedResult($result) && $this->pendingPatientUserFor($email)) {
                return $this->redirectToVerification($email, $supabase);
            }

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

        if ($user->role === 'patient' && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();

            if (! $user->patient_id) {
                $linker->reconcile($user);
                $user->refresh();
            }
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->to($this->postLoginDestination($request, $user));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function homeFor(User $user): string
    {
        if ($user->role === 'patient') {
            return $user->patient_id
                ? route('patient.dashboard')
                : route('patient.account-review');
        }

        return route('dashboard');
    }

    private function pendingPatientUserFor(string $email): ?User
    {
        return User::where('email', $email)
            ->where('role', 'patient')
            ->whereNull('email_verified_at')
            ->first();
    }

    private function redirectToVerification(string $email, SupabaseAuth $supabase)
    {
        $resent = $supabase->resendVerification($email, route('verify-email'));

        return redirect()->route('verify-email')
            ->with('verification_email', $email)
            ->with('status', $resent['ok'] ?? false
                ? 'Your patient account still needs email verification. We sent a new 6-digit code to your email.'
                : 'Your patient account still needs email verification. Enter your code below or request a new one.');
    }

    private function isEmailNotConfirmedResult(array $result): bool
    {
        $code = strtolower((string) ($result['code'] ?? ''));
        if (in_array($code, ['email_not_confirmed', 'email_not_verified'], true)) {
            return true;
        }

        $message = strtolower((string) ($result['message'] ?? ''));

        return str_contains($message, 'email not confirmed')
            || str_contains($message, 'email not verified');
    }

    private function postLoginDestination(Request $request, User $user): string
    {
        $intended = $request->session()->pull('url.intended');

        if (! $intended || ! $this->isSameHostUrl($request, $intended)) {
            return $this->homeFor($user);
        }

        $path = parse_url($intended, PHP_URL_PATH) ?: '/';

        if ($path === '/book-appointment') {
            if ($user->role === 'patient') {
                return $user->patient_id
                    ? route('patient.appointments.create')
                    : route('patient.account-review');
            }

            return route('appointments.create');
        }

        if ($user->role === 'patient') {
            if (! str_starts_with($path, '/patient')) {
                return $this->homeFor($user);
            }

            if (! $user->patient_id && $path !== '/patient/account-review') {
                return route('patient.account-review');
            }

            return $intended;
        }

        if (str_starts_with($path, '/patient')) {
            return route('dashboard');
        }

        if (in_array($path, ['/login', '/register', '/verify-email', '/verify-email/confirm'], true)) {
            return $this->homeFor($user);
        }

        return $intended;
    }

    private function isSameHostUrl(Request $request, string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return ! $host || $host === $request->getHost();
    }
}
