@extends('layouts.app')
@section('page_title', 'Add Staff')

@section('content')
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('users.index') }}" class="hover:text-emerald-600 transition">Users & Roles</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Add Staff</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add Staff Member</h1>
    <a href="{{ route('users.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="{{ route('users.store') }}" method="POST">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-6">

            {{-- Account info --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Account Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Maria" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Reyes" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="staff@dentalcare.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">License No. <span class="text-xs text-slate-400">(Dentists only)</span></label>
                        <input type="text" name="license_no" value="{{ old('license_no') }}" placeholder="e.g. 0012345" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Temporary Password <span class="text-red-500">*</span></label>
                        <input type="text" name="password" placeholder="Min. 8 characters" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required minlength="8" />
                    </div>
                </div>
            </div>

            {{-- Role & access --}}
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
                            <input type="radio" name="role" value="{{ $r['value'] }}" class="sr-only peer" {{ old('role', 'dentist') === $r['value'] ? 'checked' : '' }} />
                            <div class="border border-slate-200 rounded-xl p-4 flex items-center gap-4 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 hover:bg-slate-50 transition">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-lg {{ $r['color'] }}">{{ $r['label'] }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">{{ $r['desc'] }}</div>
                                </div>
                                <div class="h-4 w-4 rounded-full border-2 border-slate-300 peer-checked:border-emerald-500 flex items-center justify-center shrink-0">
                                    <div class="h-2 w-2 rounded-full bg-emerald-500 hidden peer-checked:block"></div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">

            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                    <p class="text-xs text-blue-700">Give this temporary password to the staff member directly — they can sign in immediately with it.</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Status</h3>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('users.index') }}">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    <i class="fa-solid fa-user-plus mr-1"></i> Create Account
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
