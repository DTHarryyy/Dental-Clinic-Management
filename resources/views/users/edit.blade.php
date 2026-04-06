@extends('layouts.app')
@section('page_title', 'Edit User')

@section('content')
@php $uid = $id ?? 2; @endphp

<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="/users" class="hover:text-emerald-600 transition">Users & Roles</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Edit User</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Edit Staff Member</h1>
    <a href="/users" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="#" method="POST">
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
                        <input type="text" value="James" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                        <input type="text" value="Lim" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" value="jlim@dentalcare.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                        <input type="tel" value="0917-555-0202" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">License No.</label>
                        <input type="text" value="0054321" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
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
                            <input type="radio" name="role" value="{{ $r['value'] }}" class="sr-only peer" {{ $r['value'] === 'dentist' ? 'checked' : '' }} />
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
                <p class="text-xs text-slate-500 mb-4">Send a password reset link to this user's email.</p>
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
                    <i class="fa-solid fa-key text-slate-400"></i> Send Reset Link
                </button>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Status</h3>
                <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option selected>Active</option>
                    <option>Inactive</option>
                </select>
            </div>

            <div class="bg-red-50 border border-red-100 rounded-2xl p-4">
                <h3 class="font-semibold text-sm text-red-700 mb-2">Danger Zone</h3>
                <p class="text-xs text-red-600 mb-3">Removing a user revokes their access immediately.</p>
                <button type="button" class="w-full py-2 rounded-xl border border-red-200 text-red-600 font-semibold text-sm hover:bg-red-100 transition">
                    Remove User
                </button>
            </div>

            <div class="flex flex-col gap-3">
                <a href="/users">
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
