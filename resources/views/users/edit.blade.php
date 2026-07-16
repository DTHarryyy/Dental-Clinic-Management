@extends('layouts.app')
@section('page_title', 'Edit User')

@section('content')
@php
    $nameParts = explode(' ', $staffUser->name, 2);
    $firstName = old('first_name', $nameParts[0] ?? '');
    $lastName = old('last_name', $nameParts[1] ?? '');
@endphp

<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('users.index') }}" class="hover:text-emerald-600 transition">Users & Roles</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Edit User</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Edit Staff Member</h1>
    <a href="{{ route('users.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>


<form action="{{ route('users.update', $staffUser) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-6">

            {{-- Account info --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Account Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                        <input type="text" name="first_name" value="{{ $firstName }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                        <input type="text" name="last_name" value="{{ $lastName }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $staffUser->email) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                        <input type="tel" name="phone" value="{{ old('phone', $staffUser->phone) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">License No.</label>
                        <input type="text" name="license_no" value="{{ old('license_no', $staffUser->license_no) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            {{-- Role --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Role & Access</h2>
                <div class="space-y-3">
                    @php
                        $roleOptions = [
                            ['value' => 'admin',        'label' => 'Admin',        'desc' => 'Full access to all modules including users and settings', 'color' => 'bg-violet-100 text-violet-700'],
                            ['value' => 'dentist',      'label' => 'Dentist',      'desc' => 'Access to patients, dental records, and appointments',     'color' => 'bg-blue-100 text-blue-700'],
                            ['value' => 'receptionist', 'label' => 'Receptionist', 'desc' => 'Access to appointments, patients, and billing',             'color' => 'bg-amber-100 text-amber-700'],
                        ];
                    @endphp
                    @foreach ($roleOptions as $r)
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="{{ $r['value'] }}" class="sr-only peer" {{ old('role', $staffUser->role) === $r['value'] ? 'checked' : '' }} />
                            <div class="border border-slate-200 rounded-xl p-4 flex items-center gap-4 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 hover:bg-slate-50 transition">
                                <div class="flex-1">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-lg {{ $r['color'] }}">{{ $r['label'] }}</span>
                                    <div class="text-xs text-slate-500 mt-1">{{ $r['desc'] }}</div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Reset password --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-1">Reset Password</h2>
                <p class="text-xs text-slate-500 mb-4">Leave blank to keep the current password.</p>
                <input type="text" name="password" placeholder="New password (min. 8 characters)" minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Status</h3>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="active" {{ old('status', $staffUser->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $staffUser->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            @if ($staffUser->id !== auth()->id())
                <div class="bg-red-50 border border-red-100 rounded-2xl p-4">
                    <h3 class="font-semibold text-sm text-red-700 mb-2">Danger Zone</h3>
                    <p class="text-xs text-red-600 mb-3">Removing a user revokes their access immediately.</p>
                    <form action="{{ route('users.destroy', $staffUser) }}" method="POST" onsubmit="return confirm('Remove this staff account?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-2 rounded-xl border border-red-200 text-red-600 font-semibold text-sm hover:bg-red-100 transition">
                            Remove User
                        </button>
                    </form>
                </div>
            @endif

            <div class="flex flex-col gap-3">
                <a href="{{ route('users.index') }}">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
