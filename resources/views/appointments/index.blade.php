@extends('layouts.app')
@section('page_title', 'Appointments')

@section('content')
@php
    $appointments = [
           ['id' => 1,  'patient' => 'Carmela Villanueva', 'service' => 'Dental Checkup',    'date' => 'Apr 6, 2026', 'time' => '09:30 AM', 'dentist' => 'Dr. Reyes', 'status' => 'Confirmed'],
           ['id' => 2,  'patient' => 'Ramon Pascual',      'service' => 'Tooth Extraction',  'date' => 'Apr 6, 2026', 'time' => '11:00 AM', 'dentist' => 'Dr. Reyes', 'status' => 'Pending'],
           ['id' => 3,  'patient' => 'Liza Mercado',       'service' => 'Braces Adjusment',  'date' => 'Apr 7, 2026', 'time' => '02:00 PM', 'dentist' => 'Dr. Reyes', 'status' => 'Confirmed'],
           ['id' => 4,  'patient' => 'Benjie Cruz',        'service' => 'Consultation',      'date' => 'Apr 7, 2026', 'time' => '03:30 PM', 'dentist' => 'Dr. Reyes', 'status' => 'Completed'],
    ];

    $statusColors = [
        'Confirmed' => 'bg-emerald-100 text-emerald-700',
        'Pending'   => 'bg-amber-100 text-amber-700',
        'Completed' => 'bg-blue-100 text-blue-700',
        'Cancelled' => 'bg-red-100 text-red-700',
    ];
@endphp

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Appointments</h1>
        <p class="text-slate-500 text-sm mt-0.5">Manage and schedule all patient appointments</p>
    </div>
    <a href="/appointments/create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-plus"></i> New Appointment
    </a>
</div>

{{-- Summary mini cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @php
        $summary = [
            ['label' => "Today",     'value' => 2, 'bg' => 'bg-blue-50',    'color' => 'text-blue-600'],
            ['label' => 'Confirmed', 'value' => 2, 'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Pending',   'value' => 1, 'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
            ['label' => 'Completed', 'value' => 1, 'bg' => 'bg-slate-50',   'color' => 'text-slate-600'],
        ];
    @endphp
    @foreach ($summary as $s)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl {{ $s['bg'] }} flex items-center justify-center {{ $s['color'] }} font-bold text-lg">
                {{ $s['value'] }}
            </div>
            <span class="text-sm font-medium text-slate-600">{{ $s['label'] }}</span>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" placeholder="Search patient or service..." class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <input type="date" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option>All Status</option>
        <option>Confirmed</option>
        <option>Pending</option>
        <option>Completed</option>
        <option>Cancelled</option>
    </select>
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <th class="text-left px-5 py-3.5 font-semibold">Patient</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden sm:table-cell">Service</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden md:table-cell">Date & Time</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden lg:table-cell">Dentist</th>
                <th class="text-left px-5 py-3.5 font-semibold">Status</th>
                <th class="text-right px-5 py-3.5 font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($appointments as $a)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        <a href="/patients/{{ $a['id'] }}" class="font-semibold text-slate-800 hover:text-emerald-600 transition">{{ $a['patient'] }}</a>
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden sm:table-cell">{{ $a['service'] }}</td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <div class="font-medium text-slate-700">{{ $a['date'] }}</div>
                        <div class="text-xs text-slate-400">{{ $a['time'] }}</div>
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden lg:table-cell">{{ $a['dentist'] }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $statusColors[$a['status']] }}">
                            {{ $a['status'] }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if ($a['status'] === 'Pending')
                                <button class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition">Confirm</button>
                            @endif
                            @if ($a['status'] !== 'Completed' && $a['status'] !== 'Cancelled')
                                <button class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition">Cancel</button>
                            @endif
                            @if ($a['status'] === 'Confirmed')
                                <a href="/records/create" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 transition">+ Record</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
        <span>Showing 1–9 of 9 appointments</span>
        <div class="flex items-center gap-1">
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium">← Prev</button>
            <button class="px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold">1</button>
            <button class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium">Next →</button>
        </div>
    </div>
</div>
@endsection
