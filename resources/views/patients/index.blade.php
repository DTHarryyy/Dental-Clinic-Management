@extends('layouts.app')
@section('page_title', 'Patients')

@section('content')
@php
    $patients = [
           ['id' => 1, 'name' => 'Princess',    'age' => 36, 'gender' => 'Male',   'contact' => '0917-123-4567', 'lastVisit' => 'Apr 2, 2026',  'status' => 'Active'],
           ['id' => 2, 'name' => 'Michaela',     'age' => 27, 'gender' => 'Female', 'contact' => '0918-234-5678', 'lastVisit' => 'Mar 28, 2026', 'status' => 'Inactive'],
           ['id' => 3, 'name' => 'Roxanne',  'age' => 42, 'gender' => 'Male',   'contact' => '0919-345-6789', 'lastVisit' => 'Mar 15, 2026', 'status' => 'Active'],
    ];
@endphp

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Patients</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ count($patients) }} registered patients</p>
    </div>
    <a href="/patients/create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-user-plus"></i> Add Patient
    </a>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" placeholder="Search patients..." class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option>All Status</option>
        <option>Active</option>
        <option>Inactive</option>
    </select>
    <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option>All Gender</option>
        <option>Male</option>
        <option>Female</option>
    </select>
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <th class="text-left px-5 py-3.5 font-semibold">Patient</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden sm:table-cell">Contact</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden md:table-cell">Age / Gender</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden lg:table-cell">Last Visit</th>
                <th class="text-left px-5 py-3.5 font-semibold">Status</th>
                <th class="text-right px-5 py-3.5 font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($patients as $p)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper(substr($p['name'], 0, 1) . substr(strrchr($p['name'], ' '), 1, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800">{{ $p['name'] }}</div>
                                <div class="text-xs text-slate-400">ID #{{ str_pad($p['id'], 4, '0', STR_PAD_LEFT) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden sm:table-cell">{{ $p['contact'] }}</td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <span class="text-slate-600">{{ $p['age'] }} yrs</span>
                        <span class="text-slate-400 ml-1">· {{ $p['gender'] }}</span>
                    </td>
                    <td class="px-5 py-4 text-slate-500 hidden lg:table-cell">{{ $p['lastVisit'] }}</td>
                    <td class="px-5 py-4">
                        @if($p['status'] === 'Active')
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
                            <a href="/patients/{{ $p['id'] }}"      class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">View</a>
                            <a href="/patients/{{ $p['id'] }}/edit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Edit</a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
        <span>Showing 1–8 of 128 patients</span>
        <div class="flex items-center gap-1">
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium">← Prev</button>
            <button class="px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold">1</button>
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium">2</button>
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium">3</button>
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium">Next →</button>
        </div>
    </div>
</div>
@endsection
