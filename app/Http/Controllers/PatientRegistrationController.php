<?php

namespace App\Http\Controllers;

use App\Models\PatientConsent;
use App\Models\PublicSiteSetting;
use App\Models\User;
use App\Services\PatientAccountLinker;
use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'privacy_accepted' => ['accepted'],
            'terms_accepted' => ['accepted'],
        ]);

        $email = PatientAccountLinker::normalizeEmail($data['email']);
        $mobile = preg_replace('/\s+/', ' ', trim($data['mobile']));
        $settings = PublicSiteSetting::current();
        $redirectTo = route('verify-email');

        $created = $supabase->signUp($email, $data['password'], $redirectTo);
        if (! $created['ok']) {
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
        } catch (\Throwable $exception) {
            $supabase->adminDeleteUser($uid);
            throw $exception;
        }

        return redirect()->route('verify-email')
            ->with('status', 'We sent a 6-digit verification code to your email address.')
            ->with('verification_email', $email);
    }
}
