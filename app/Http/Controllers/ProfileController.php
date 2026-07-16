<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'license_no' => $data['license_no'] ?? null,
            ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        return $this->respond($request, back()->with('status', 'Profile updated successfully.'));
    }
}
