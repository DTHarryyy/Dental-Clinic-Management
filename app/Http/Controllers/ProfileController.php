<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request, SupabaseAuth $supabase)
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            $verified = $supabase->signIn($user->email, $data['current_password']);
            if (! $verified['ok']) {
                throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
            }
            $updated = $supabase->adminUpdateUser($user->supabase_uid, ['password' => $data['password']]);
            if (! $updated['ok']) {
                throw ValidationException::withMessages(['password' => $updated['message'] ?? 'Could not update your password.']);
            }
        }

        $user->update([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'license_no' => $data['license_no'] ?? null,
            ...(! empty($data['password']) ? ['must_change_password' => false] : []),
        ]);

        return $this->respond($request, back()->with('status', 'Profile updated successfully.'));
    }
}
