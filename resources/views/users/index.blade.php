@extends('layouts.app')
@section('page_title', 'Users & Roles')

@section('content')
@php
    $users = [
           ['id' => 1, 'name' => 'Dr. Maria Reyes',    'email' => 'reyes@dentalcare.com',    'role' => 'Admin',        'status' => 'Active',   'lastLogin' => 'Today, 9:14 AM'],
           ['id' => 2, 'name' => 'Dr. James Lim',      'email' => 'jlim@dentalcare.com',     'role' => 'Dentist',      'status' => 'Active',   'lastLogin' => 'Today, 8:50 AM'],
           ['id' => 3, 'name' => 'Anna Cruz',          'email' => 'acruz@dentalcare.com',    'role' => 'Receptionist', 'status' => 'Active',   'lastLogin' => 'Yesterday'],
           ['id' => 4, 'name' => 'Carlo Bautsita',     'email' => 'cbautista@dentalcare.com','role' => 'Receptionist', 'status' => 'Inactive', 'lastLogin' => 'Mar 30, 2026'],
    ];

    $roles = [
        ['name' => 'Admin',        'color' => 'bg-violet-100 text-violet-700',  'count' => 1, 'desc' => 'Full access to all modules'],
        ['name' => 'Dentist',      'color' => 'bg-blue-100 text-blue-700',      'count' => 2, 'desc' => 'Patients, records, appointments'],
        ['name' => 'Receptionist', 'color' => 'bg-amber-100 text-amber-700',    'count' => 2, 'desc' => 'Appointments, billing, patients'],
    ];

    $roleColors = [
        'Admin'        => 'bg-violet-100 text-violet-700',
        'Dentist'      => 'bg-blue-100 text-blue-700',
        'Receptionist' => 'bg-amber-100 text-amber-700',
    ];
@endphp

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Users & Roles</h1>
        <p class="text-slate-500 text-sm mt-0.5">Manage staff accounts and access permissions</p>
    </div>
    <a href="/users/create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-user-plus"></i> Invite User
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Users table --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Search / filter bar --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-48">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" placeholder="Search users..." class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
            </div>
            <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <option>All Roles</option>
                <option>Admin</option>
                <option>Dentist</option>
                <option>Receptionist</option>
            </select>
            <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <option>All Status</option>
                <option>Active</option>
                <option>Inactive</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                        <th class="text-left px-5 py-3.5 font-semibold">User</th>
                        <th class="text-left px-5 py-3.5 font-semibold hidden sm:table-cell">Role</th>
                        <th class="text-left px-5 py-3.5 font-semibold hidden lg:table-cell">Last Login</th>
                        <th class="text-left px-5 py-3.5 font-semibold">Status</th>
                        <th class="text-right px-5 py-3.5 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $u)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($u['name'], 0, 1) . substr(strrchr($u['name'], ' '), 1, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $u['name'] }}</div>
                                        <div class="text-xs text-slate-400">{{ $u['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 hidden sm:table-cell">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $roleColors[$u['role']] }}">
                                    {{ $u['role'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs hidden lg:table-cell">{{ $u['lastLogin'] }}</td>
                            <td class="px-5 py-4">
                                @if ($u['status'] === 'Active')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400 inline-block"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/users/{{ $u['id'] }}/edit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Edit</a>
                                    @if ($u['id'] !== 1)
                                        <button class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition">Remove</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
                <span>{{ count($users) }} staff members</span>
                <div class="flex items-center gap-1">
                    <button class="px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold">1</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Right panel: Roles --}}
    <div class="space-y-5">

        {{-- Roles overview --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-base text-slate-800">Roles</h2>
                <button class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <i class="fa-solid fa-plus mr-1"></i> New Role
                </button>
            </div>
            <div class="space-y-3">
                @foreach ($roles as $role)
                    <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-lg {{ $role['color'] }}">{{ $role['name'] }}</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-1">{{ $role['desc'] }}</div>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <div class="font-bold text-slate-800">{{ $role['count'] }}</div>
                            <div class="text-[10px] text-slate-400">users</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Permissions summary --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-semibold text-base text-slate-800 mb-4">Permission Matrix</h2>
            @php
                $perms = [
                    ['module' => 'Dashboard',     'admin' => true,  'dentist' => true,  'receptionist' => true],
                    ['module' => 'Patients',      'admin' => true,  'dentist' => true,  'receptionist' => true],
                    ['module' => 'Appointments',  'admin' => true,  'dentist' => true,  'receptionist' => true],
                    ['module' => 'Dental Records','admin' => true,  'dentist' => true,  'receptionist' => false],
                    ['module' => 'Billing',       'admin' => true,  'dentist' => false, 'receptionist' => true],
                    ['module' => 'Reports',       'admin' => true,  'dentist' => false, 'receptionist' => false],
                    ['module' => 'Users',         'admin' => true,  'dentist' => false, 'receptionist' => false],
                    ['module' => 'Settings',      'admin' => true,  'dentist' => false, 'receptionist' => false],
                ];
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wide">
                            <th class="text-left pb-2 font-semibold">Module</th>
                            <th class="text-center pb-2 font-semibold">Adm</th>
                            <th class="text-center pb-2 font-semibold">Dent</th>
                            <th class="text-center pb-2 font-semibold">Rec</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($perms as $p)
                            <tr>
                                <td class="py-2 text-slate-600 font-medium">{{ $p['module'] }}</td>
                                <td class="py-2 text-center">
                                    @if ($p['admin'])
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                    @else
                                        <i class="fa-solid fa-circle-xmark text-slate-200"></i>
                                    @endif
                                </td>
                                <td class="py-2 text-center">
                                    @if ($p['dentist'])
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                    @else
                                        <i class="fa-solid fa-circle-xmark text-slate-200"></i>
                                    @endif
                                </td>
                                <td class="py-2 text-center">
                                    @if ($p['receptionist'])
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                    @else
                                        <i class="fa-solid fa-circle-xmark text-slate-200"></i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
