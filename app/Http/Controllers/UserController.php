<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseAuth;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

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

    public function store(Request $request, SupabaseAuth $supabase, TransactionalEmailDispatcher $emails)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:admin,dentist,receptionist'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $temporaryPassword = Str::password(18, symbols: true);
        $result = $supabase->adminCreateUser($data['email'], $temporaryPassword);

        if (! $result['ok']) {
            return back()->withErrors(['email' => $result['message']])->withInput();
        }

        try {
            $user = DB::transaction(fn () => User::create([
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'email' => $data['email'],
                'supabase_uid' => $result['user']['id'],
                'phone' => $data['phone'] ?? null,
                'license_no' => $data['license_no'] ?? null,
                'role' => $data['role'],
                'status' => $data['status'],
                'must_change_password' => true,
                'password' => null,
            ]));
        } catch (\Throwable $e) {
            $supabase->adminDeleteUser($result['user']['id']);
            throw $e;
        }

        if ($user->status === 'active') {
            $emails->dispatch('staff_credentials', $user->email, $user, [
                'temporary_password' => $temporaryPassword,
                'login_url' => route('login'),
            ]);
        }

        return $this->respond($request, redirect()->route('users.index')->with('status', 'Staff account created successfully.'));
    }

    public function edit(User $user)
    {
        return view('users.edit', ['staffUser' => $user]);
    }

    public function update(Request $request, User $user, SupabaseAuth $supabase)
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

        try {
            $uid = $this->syncSupabaseAccount($supabase, $user, $data['email'], $data['password'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
        }

        $user->update([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'supabase_uid' => $uid,
            'phone' => $data['phone'] ?? null,
            'license_no' => $data['license_no'] ?? null,
            'role' => $data['role'],
            'status' => $data['status'],
            'password' => null,
        ]);

        return $this->respond($request, redirect()->route('users.index')->with('status', 'Staff account updated successfully.'));
    }

    public function destroy(Request $request, User $user, SupabaseAuth $supabase)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot remove your own account.']);
        }

        if ($user->supabase_uid) {
            $supabase->adminDeleteUser($user->supabase_uid);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Staff account removed.');
    }

    /**
     * Keeps the linked Supabase Auth account (email/password) in sync with local
     * edits. Legacy rows with no linked account get one created lazily if a new
     * password is set. Returns the Supabase user id to persist on the local row.
     */
    private function syncSupabaseAccount(SupabaseAuth $supabase, User $user, string $email, ?string $password): ?string
    {
        if ($user->supabase_uid) {
            $attributes = [];

            if ($email !== $user->email) {
                $attributes['email'] = $email;
                $attributes['email_confirm'] = true;
            }

            if ($password) {
                $attributes['password'] = $password;
            }

            if ($attributes) {
                $result = $supabase->adminUpdateUser($user->supabase_uid, $attributes);

                if (! $result['ok']) {
                    throw new RuntimeException($result['message']);
                }
            }

            return $user->supabase_uid;
        }

        if ($password) {
            $result = $supabase->adminCreateUser($email, $password);

            if (! $result['ok']) {
                throw new RuntimeException($result['message']);
            }

            return $result['user']['id'];
        }

        return null;
    }
}
