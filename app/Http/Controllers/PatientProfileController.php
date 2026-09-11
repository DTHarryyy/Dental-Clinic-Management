<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('patient.profile.show', [
            'user' => $request->user(),
            'patient' => $request->user()->patient,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();
        DB::transaction(function () use ($user, $data): void {
            $user->update([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'phone' => $data['mobile'] ?? null,
            ]);

            $user->patient->update($data);
        });

        return redirect()->route('patient.profile')->with('status', 'Profile updated.');
    }

    public function security(Request $request, SupabaseAuth $supabase)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $verified = $supabase->signIn($user->email, $data['current_password']);
        if (! $verified['ok']) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $uid = $user->supabase_uid ?: ($verified['user']['id'] ?? null);
        if (! $uid) {
            throw ValidationException::withMessages([
                'current_password' => 'Your authentication account could not be linked. Contact the clinic.',
            ]);
        }

        $updated = $supabase->adminUpdateUser($uid, ['password' => $data['password']]);
        if (! $updated['ok']) {
            throw ValidationException::withMessages([
                'password' => $updated['message'] ?? 'Could not update your password.',
            ]);
        }

        return redirect(route('patient.profile').'#security')->with('status', 'Password updated.');
    }
}
