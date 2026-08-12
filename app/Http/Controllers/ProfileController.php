<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function updateDetails(Request $request)
    {
        $user = $request->user();
        $data = $request->validateWithBag('details', [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => trim($data['full_name']),
            'phone' => $data['phone'] ?? null,
            'license_no' => $user->role === 'dentist' ? ($data['license_no'] ?? null) : $user->license_no,
        ]);

        return $this->respond($request, redirect(route('profile.show').'#personal-information')->with('status', 'Personal information updated.'));
    }

    public function updateSecurity(Request $request, SupabaseAuth $supabase)
    {
        $user = $request->user();
        $data = $request->validateWithBag('security', [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $emailChanged = $data['email'] !== $user->email;
        $passwordChanged = filled($data['password'] ?? null);

        if (! $emailChanged && ! $passwordChanged) {
            return $this->respond($request, redirect(route('profile.show').'#security')->with('status', 'No security changes to save.'));
        }

        if (blank($data['current_password'] ?? null)) {
            throw ValidationException::withMessages([
                'current_password' => 'Enter your current password to change your login credentials.',
            ]);
        }

        $verified = $supabase->signIn($user->email, $data['current_password']);
        if (! $verified['ok']) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $uid = $user->supabase_uid ?: ($verified['user']['id'] ?? null);
        if (! $uid) {
            throw ValidationException::withMessages([
                'email' => 'Your authentication account could not be linked. Contact an administrator.',
            ]);
        }

        $attributes = [];
        if ($emailChanged) {
            $attributes['email'] = $data['email'];
            $attributes['email_confirm'] = true;
        }
        if ($passwordChanged) {
            $attributes['password'] = $data['password'];
        }

        $updated = $supabase->adminUpdateUser($uid, $attributes);
        if (! $updated['ok']) {
            throw ValidationException::withMessages([
                $emailChanged ? 'email' : 'password' => $updated['message'] ?? 'Could not update your login credentials.',
            ]);
        }

        $oldEmail = $user->email;
        try {
            DB::transaction(fn () => $user->update([
                'email' => $data['email'],
                'supabase_uid' => $uid,
                ...($passwordChanged ? ['must_change_password' => false] : []),
            ]));
        } catch (\Throwable $exception) {
            if ($emailChanged) {
                $supabase->adminUpdateUser($uid, ['email' => $oldEmail, 'email_confirm' => true]);
            }
            throw $exception;
        }

        return $this->respond($request, redirect(route('profile.show').'#security')->with('status', 'Security settings updated.'));
    }
}
