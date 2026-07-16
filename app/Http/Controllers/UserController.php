<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        $roles = [
            ['name' => 'Admin', 'color' => 'bg-violet-100 text-violet-700', 'count' => $users->where('role', 'admin')->count(), 'desc' => 'Full access to all modules'],
            ['name' => 'Dentist', 'color' => 'bg-blue-100 text-blue-700', 'count' => $users->where('role', 'dentist')->count(), 'desc' => 'Patients, records, appointments'],
            ['name' => 'Receptionist', 'color' => 'bg-amber-100 text-amber-700', 'count' => $users->where('role', 'receptionist')->count(), 'desc' => 'Appointments, billing, patients'],
        ];

        return view('users.index', ['users' => $users, 'roles' => $roles]);
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,dentist,receptionist'],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'license_no' => $data['license_no'] ?? null,
            'role' => $data['role'],
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
        ]);

        return $this->respond($request, redirect()->route('users.index')->with('status', 'Staff account created successfully.'));
    }

    public function edit(User $user)
    {
        return view('users.edit', ['staffUser' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,dentist,receptionist'],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->update([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'license_no' => $data['license_no'] ?? null,
            'role' => $data['role'],
            'status' => $data['status'],
            ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        return $this->respond($request, redirect()->route('users.index')->with('status', 'Staff account updated successfully.'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot remove your own account.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Staff account removed.');
    }
}
