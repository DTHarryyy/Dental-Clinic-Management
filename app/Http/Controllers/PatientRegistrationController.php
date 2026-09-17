<?php

namespace App\Http\Controllers;

use App\Models\PatientConsent;
use App\Models\PublicSiteSetting;
use App\Models\User;
use App\Services\PatientAccountLinker;
use App\Services\SupabaseAuth;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PatientRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register', [
            'site' => PublicSiteSetting::current(),
        ]);
    }

    public function store(Request $request, SupabaseAuth $supabase, PatientAccountLinker $linker)
    {
        $request->merge([
            'email' => PatientAccountLinker::normalizeEmail((string) $request->input('email')),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'privacy_accepted' => ['accepted'],
            'terms_accepted' => ['accepted'],
        ]);

        $email = $data['email'];
        $mobile = preg_replace('/\s+/', ' ', trim($data['mobile']));
        $settings = PublicSiteSetting::current();
        $redirectTo = route('verify-email');

        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $this->handleExistingAccount($request, $supabase, $existing, 'existing_unverified');
        }

        $created = $supabase->signUp($email, $data['password'], $redirectTo);
        if (! $created['ok']) {
            if ($this->isExistingAuthAccountError($created)) {
                Log::warning('patient_registration_auth_mismatch', [
                    'email_hash' => hash('sha256', $email),
                    'auth_code' => $created['code'] ?? null,
                ]);

                throw ValidationException::withMessages([
                    'email' => 'An authentication account already exists for this email. Sign in or reset your password.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => $created['message'] ?? 'Could not create your authentication account.',
            ]);
        }

        $uid = $created['user']['id'] ?? null;
        if (! $uid) {
            throw ValidationException::withMessages([
                'email' => 'The authentication service did not return an account ID.',
            ]);
        }

        try {
            DB::transaction(function () use ($data, $email, $mobile, $uid, $settings, $request, $linker): void {
                $user = User::create([
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'email' => $email,
                    'phone' => $mobile,
                    'role' => 'patient',
                    'status' => 'active',
                    'supabase_uid' => $uid,
                    'password' => null,
                    'email_verified_at' => null,
                ]);

                foreach ([
                    'privacy' => $settings->privacy_policy_version ?: 'privacy-v1',
                    'terms' => $settings->terms_version ?: 'terms-v1',
                ] as $type => $version) {
                    PatientConsent::create([
                        'user_id' => $user->id,
                        'type' => $type,
                        'version' => $version,
                        'accepted_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => mb_substr((string) $request->userAgent(), 0, 500) ?: null,
                    ]);
                }

                $linker->reconcile($user, [
                    'first_name' => trim($data['first_name']),
                    'last_name' => trim($data['last_name']),
                    'mobile' => $mobile,
                ]);
            });
        } catch (QueryException $exception) {
            $winner = User::where('email', $email)->first();

            if ($this->isUsersEmailUniqueViolation($exception) && $this->isPendingActivePatient($winner)) {
                return $this->redirectPendingPatientToVerification($request, $supabase, $winner, 'email_unique_race');
            }

            $this->cleanupCreatedAuthUser($supabase, $uid, $exception);
            throw $exception;
        } catch (\Throwable $exception) {
            $this->cleanupCreatedAuthUser($supabase, $uid, $exception);
            throw $exception;
        }

        return redirect()->route('verify-email')
            ->with('status', 'We sent a 6-digit verification code to your email address.')
            ->with('verification_email', $email);
    }

    private function handleExistingAccount(Request $request, SupabaseAuth $supabase, User $user, string $reason)
    {
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'This account is inactive. Contact the clinic for assistance.',
            ]);
        }

        if ($this->isPendingActivePatient($user)) {
            return $this->redirectPendingPatientToVerification($request, $supabase, $user, $reason);
        }

        throw ValidationException::withMessages([
            'email' => 'An account already exists for this email. Sign in or reset your password.',
        ]);
    }

    private function redirectPendingPatientToVerification(Request $request, SupabaseAuth $supabase, User $user, string $reason)
    {
        $key = 'patient-registration-verification-resend:'.hash('sha256', $user->email.'|'.$request->ip());
        $attempted = false;
        $resent = false;

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 600);
            $attempted = true;
            $resent = (bool) ($supabase->resendVerification($user->email, route('verify-email'))['ok'] ?? false);
        }

        Log::info('patient_registration_retry_recovered', [
            'user_id' => $user->id,
            'reason' => $reason,
            'resend_attempted' => $attempted,
            'resend_succeeded' => $resent,
        ]);

        return redirect()->route('verify-email')
            ->with('status', $resent
                ? 'Your account was already created. We sent a new 6-digit verification code to your email.'
                : 'Your account was already created. Enter your existing verification code or request a new one.')
            ->with('verification_email', $user->email);
    }

    private function isPendingActivePatient(?User $user): bool
    {
        return $user !== null
            && $user->role === 'patient'
            && $user->status === 'active'
            && $user->email_verified_at === null;
    }

    /** @param array<string, mixed> $result */
    private function isExistingAuthAccountError(array $result): bool
    {
        $code = strtolower((string) ($result['code'] ?? ''));
        $message = strtolower((string) ($result['message'] ?? ''));

        return in_array($code, ['email_exists', 'user_already_exists', 'email_already_exists'], true)
            || str_contains($message, 'already exists')
            || str_contains($message, 'already registered')
            || str_contains($message, 'email exists');
    }

    private function isUsersEmailUniqueViolation(QueryException $exception): bool
    {
        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = strtolower($exception->getMessage());

        return in_array($state, ['23505', '23000'], true)
            && (str_contains($message, 'users_email_unique')
                || str_contains($message, 'unique constraint failed: users.email'));
    }

    private function cleanupCreatedAuthUser(SupabaseAuth $supabase, string $uid, \Throwable $exception): void
    {
        if (User::where('supabase_uid', $uid)->exists()) {
            return;
        }

        if (! $supabase->adminDeleteUser($uid)) {
            Log::warning('patient_registration_cleanup_failed', [
                'supabase_uid' => $uid,
                'exception' => $exception::class,
            ]);
        }
    }
}
