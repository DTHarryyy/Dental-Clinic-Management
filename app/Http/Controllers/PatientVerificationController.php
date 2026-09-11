<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PatientAccountLinker;
use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PatientVerificationController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.verify-email', [
            'email' => old('email', $request->session()->get('verification_email', $request->query('email', ''))),
        ]);
    }

    public function confirm(Request $request)
    {
        if (! $request->query('token_hash')) {
            return redirect()->route('verify-email', array_filter(['email' => $request->query('email')]));
        }

        return view('auth.verify-confirm', [
            'tokenHash' => $request->query('token_hash'),
            'type' => $request->query('type', 'email'),
            'email' => $request->query('email'),
        ]);
    }

    public function consume(Request $request, SupabaseAuth $supabase, PatientAccountLinker $linker)
    {
        if ($request->filled('token_hash')) {
            return $this->consumeTokenHash($request, $supabase, $linker);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
            'type' => ['nullable', 'string', 'max:30'],
        ]);

        $email = PatientAccountLinker::normalizeEmail($data['email']);
        $verified = $supabase->verifyEmailCode($email, $data['code'], $data['type'] ?? 'email');
        if (! $verified['ok']) {
            throw ValidationException::withMessages([
                'code' => $verified['message'] ?? 'The verification code is invalid or expired.',
            ]);
        }

        return $this->markLocalPatientVerified($verified, $email, $linker);
    }

    public function resend(Request $request, SupabaseAuth $supabase)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', PatientAccountLinker::normalizeEmail($data['email']))
            ->where('role', 'patient')
            ->first();

        if ($user && ! $user->email_verified_at) {
            $supabase->resendVerification($user->email, route('verify-email'));
        }

        return back()
            ->with('status', 'If that patient account exists, a new verification code has been sent.')
            ->with('verification_email', PatientAccountLinker::normalizeEmail($data['email']));
    }

    private function consumeTokenHash(Request $request, SupabaseAuth $supabase, PatientAccountLinker $linker)
    {
        $data = $request->validate([
            'token_hash' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:30'],
        ]);

        $verified = $supabase->verifyEmailToken($data['token_hash'], $data['type'] ?? 'email');
        if (! $verified['ok']) {
            throw ValidationException::withMessages([
                'token_hash' => $verified['message'] ?? 'The verification link is invalid or expired.',
            ]);
        }

        return $this->markLocalPatientVerified($verified, null, $linker);
    }

    private function markLocalPatientVerified(array $verified, ?string $fallbackEmail, PatientAccountLinker $linker)
    {
        $uid = $verified['user']['id'] ?? null;
        $email = $verified['user']['email'] ?? $fallbackEmail;
        $user = $uid ? User::where('supabase_uid', $uid)->first() : null;
        $user ??= $email ? User::where('email', PatientAccountLinker::normalizeEmail($email))->first() : null;

        if (! $user || $user->role !== 'patient') {
            throw ValidationException::withMessages([
                'email' => 'No patient account is waiting for this verification code.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?: now(),
            'supabase_uid' => $uid ?: $user->supabase_uid,
        ])->save();

        if (! $user->patient_id) {
            $linker->reconcile($user);
        }

        return redirect()->route('login')->with('status', 'Email verified. You can now sign in.');
    }
}
