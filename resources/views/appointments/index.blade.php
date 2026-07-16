@extends('layouts.app')
@section('page_title', 'Appointments')

@section('content')
@php
    $statusColors = [
        'confirmed' => 'bg-emerald-100 text-emerald-700',
        'pending'   => 'bg-amber-100 text-amber-700',
        'completed' => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
@endphp

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Appointments</h1>
        <p class="text-slate-500 text-sm mt-0.5">Manage and schedule all patient appointments</p>
    </div>
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'appointment-create' } }))" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-plus"></i> New Appointment
    </button>
</div>

{{-- Summary mini cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['label' => "Today",     'value' => $summary['today'],     'bg' => 'bg-blue-50',    'color' => 'text-blue-600'],
            ['label' => 'Confirmed', 'value' => $summary['confirmed'], 'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Pending',   'value' => $summary['pending'],   'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
            ['label' => 'Completed', 'value' => $summary['completed'], 'bg' => 'bg-slate-50',   'color' => 'text-slate-600'],
        ];
    @endphp
    @foreach ($cards as $s)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl {{ $s['bg'] }} flex items-center justify-center {{ $s['color'] }} font-bold text-lg">
                {{ $s['value'] }}
            </div>
            <span class="text-sm font-medium text-slate-600">{{ $s['label'] }}</span>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" data-auto-filter="appointments" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patient or service..." autocomplete="off" class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <input type="date" name="date" value="{{ request('date') }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    <select name="status" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option {{ !request('status') ? 'selected' : '' }}>All Status</option>
        <option {{ request('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
        <option {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
        <option {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
        <option {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
    </select>
</form>

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
            @forelse ($appointments as $a)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        @if ($a->patient)
                            <a href="{{ route('patients.show', $a->patient) }}" class="font-semibold text-slate-800 hover:text-emerald-600 transition">{{ $a->full_name }}</a>
                        @else
                            <span class="font-semibold text-slate-800">{{ $a->full_name }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden sm:table-cell">{{ $a->service }}</td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <div class="font-medium text-slate-700">{{ $a->appointment_date->format('M j, Y') }}</div>
                        <div class="text-xs text-slate-400">{{ $a->appointment_time ?? '—' }}</div>
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden lg:table-cell">{{ $a->dentist->name ?? '—' }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $statusColors[$a->status] }}">
                            {{ ucfirst($a->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if ($a->status === 'pending')
                                <form action="{{ route('appointments.status', $a) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="confirmed" />
                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition">Confirm</button>
                                </form>
                            @endif
                            @if (!in_array($a->status, ['completed', 'cancelled']))
                                <form action="{{ route('appointments.status', $a) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled" />
                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition">Cancel</button>
                                </form>
                            @endif
                            @if ($a->status === 'confirmed')
                                <form action="{{ route('appointments.status', $a) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="completed" />
                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Complete</button>
                                </form>
                                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'record-create' } }))" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 transition">+ Record</button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-400">No appointments found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($appointments->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $appointments->links() }}
        </div>
    @endif
</div>

<x-modal name="appointment-create" title="Book Appointment" max-width="3xl">
    @include('appointments._form-dialog')
</x-modal>

<x-modal name="patient-create" title="Add New Patient" max-width="3xl" body-class="flex flex-col min-h-0">
    @include('patients._form-dialog', ['patient' => null])
</x-modal>

<x-modal name="record-create" title="Add Treatment Record" max-width="3xl">
    @include('records._form-dialog')
</x-modal>
@endsection
