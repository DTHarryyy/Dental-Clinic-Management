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
    $canWriteRecords = auth()->user()->canWriteRecords();
    $canManageBilling = auth()->user()->hasPermission(\App\Enums\Permission::BillingManage);
@endphp

{{-- Header --}}
<div class="page-header">
    <div>
        <h1 class="page-title">Appointments</h1>
        <p class="text-slate-500 text-sm mt-0.5">Manage and schedule all patient appointments</p>
    </div>
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'appointment-create' } }))" class="primary-action">
        <i class="fa-solid fa-plus"></i> New Appointment
    </button>
</div>

{{-- Summary mini cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    @php
        $cards = [
            ['label' => "Today",     'value' => $summary['today'],     'bg' => 'bg-blue-50',    'color' => 'text-blue-600'],
            ['label' => 'Confirmed', 'value' => $summary['confirmed'], 'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Pending',   'value' => $summary['pending'],   'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
            ['label' => 'Completed', 'value' => $summary['completed'], 'bg' => 'bg-slate-50',   'color' => 'text-slate-600'],
        ];
    @endphp
    @foreach ($cards as $s)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3 sm:p-4 flex min-w-0 items-center gap-2 sm:gap-3">
            <div class="h-10 w-10 rounded-xl {{ $s['bg'] }} flex items-center justify-center {{ $s['color'] }} font-bold text-lg">
                {{ $s['value'] }}
            </div>
            <span class="text-sm font-medium text-slate-600">{{ $s['label'] }}</span>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" data-auto-filter="appointments" class="filter-bar filter-controls">
    <div class="relative min-w-0 flex-1">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patient or service..." autocomplete="off" class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <input type="date" name="date" value="{{ request('date') }}" class="filter-control" />
    <select name="status" class="filter-control">
        <option {{ !request('status') ? 'selected' : '' }}>All Status</option>
        <option {{ request('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
        <option {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
        <option {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
        <option {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
    </select>
</form>

{{-- Table --}}
@if (request()->integer('appointment'))
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <span><i class="fa-solid fa-magnifying-glass mr-1.5"></i>Showing the appointment selected from global search.</span>
        <a href="{{ route('appointments.index') }}" class="font-semibold hover:underline">Clear selected result</a>
    </div>
@endif
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="responsive-stack-table appointment-table w-full text-sm">
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
                    <td class="px-5 py-4 text-slate-600 hidden sm:table-cell">{{ $a->service_names }}</td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <div class="font-medium text-slate-700">{{ ($a->scheduled_start_at ?? $a->requested_start_at ?? $a->preferred_date ?? $a->appointment_date)->setTimezone('Asia/Manila')->format('M j, Y') }}</div>
                        <div class="text-xs text-slate-400">@if($a->scheduling_mode === 'first_come' && $a->scheduled_start_at)First come from {{ $a->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A') }}@elseif($a->scheduled_start_at){{ $a->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A') }}–{{ $a->scheduled_end_at->setTimezone('Asia/Manila')->format('g:i A') }}@elseif($a->requested_start_at)Requested {{ $a->requested_start_at->setTimezone('Asia/Manila')->format('g:i A') }}–{{ $a->requested_end_at->setTimezone('Asia/Manila')->format('g:i A') }}@else Time not set @endif</div>
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
                                    <button type="button" data-open-dialog="{{ json_encode([
                                        'id' => 'appointment-confirm',
                                        'actions' => ['confirm' => route('appointments.status', $a)],
                                        'fields' => [
                                            'availability_url' => route('appointments.availability', $a),
                                            'schedule_date' => ($a->preferred_date ?? $a->appointment_date)->toDateString(),
                                            'duration_minutes' => $a->total_duration_minutes,
                                            'dentist_id' => $a->dentist_id,
                                        ],
                                        'requested_start_at' => optional($a->requested_start_at)->toIso8601String(),
                                    ]) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition">Confirm</button>
                            @endif
                            @if (!in_array($a->status, ['completed', 'cancelled']))
                                {{-- Confirmed first: this is destructive and sits next to Complete. --}}
                                <button type="button" data-open-cancel="{{ json_encode([
                                    'action' => route('appointments.status', $a),
                                    'name' => $a->patient->name ?? $a->full_name,
                                    'when' => 'on '.$a->appointment_date->format('M j, Y').($a->appointment_time ? ' at '.$a->appointment_time : ''),
                                    'preferredDate' => ($a->preferred_date ?? $a->appointment_date)->toDateString(),
                                    'availabilityUrl' => route('public.book.availability'),
                                    'duration' => $a->total_duration_minutes,
                                ]) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition">Cancel</button>
                            @endif
                            @if ($a->status === 'cancelled')
                                {{-- Cancelled rows used to render nothing at all, stranding a misclick forever. --}}
                                <form action="{{ route('appointments.status', $a) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="pending" />
                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 transition">
                                        <i class="fa-solid fa-rotate-left mr-1 text-[10px]"></i>Reopen
                                    </button>
                                </form>
                            @endif
                            @if ($a->status === 'confirmed')
                                @if ($canWriteRecords)
                                    {{-- Completing means filing the treatment record, so Complete opens the
                                         record dialog seeded from this row rather than posting the status. --}}
                                    <button type="button" data-open-dialog="{{ json_encode([
                                        'id' => 'appointment-complete',
                                        'fields' => [
                                            'appointment_id' => $a->id,
                                            'patient_id' => $a->patient_id,
                                            'dentist_id' => $a->dentist_id,
                                            'treatment_date' => $a->appointment_date->toDateString(),
                                            // A treatment record stores one catalog procedure. For bookings
                                            // with several services, preselect the first and show the complete
                                            // readable list in the appointment summary above the field.
                                            'procedure' => $a->serviceItems->first()?->name_snapshot ?? $a->service,
                                            ...($canManageBilling ? [
                                                'treatment_fee' => $a->serviceItems->isNotEmpty() ? $a->estimated_total : ($servicePrices[$a->service] ?? null),
                                            ] : []),
                                        ],
                                        'text' => [
                                            'patient_name' => $a->patient->name ?? $a->full_name,
                                            'booked_service' => $a->service_names,
                                            'appointment_summary' => $a->appointment_date->format('M j, Y').' · '.($a->appointment_time ?: 'No time set').' · '.$a->service_names,
                                        ],
                                        'actions' => ['skip' => route('appointments.status', $a)],
                                    ]) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Complete</button>
                                @else
                                    {{-- Receptionists cannot write records (records routes are admin/dentist
                                         only), so for them Complete stays a plain status change. --}}
                                    <form action="{{ route('appointments.status', $a) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="completed" />
                                        <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Complete</button>
                                    </form>
                                @endif
                            @endif
                            @if ($a->status === 'completed')
                                @if ($a->dentalRecord)
                                    @if ($canWriteRecords)
                                        <a href="{{ route('records.show', $a->dentalRecord) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg text-teal-700 hover:bg-teal-50 transition">View record →</a>
                                    @else
                                        <span class="text-xs font-medium px-3 py-1.5 text-slate-400"><i class="fa-solid fa-check mr-1 text-[10px]"></i>Record filed</span>
                                    @endif
                                @else
                                    {{-- Completed with nothing filed: either a pre-record-feature booking or one
                                         closed via "mark complete without a record". Say so rather than leaving
                                         an empty cell that reads like the page failed to load. --}}
                                    <span class="text-xs font-medium px-2 py-1.5 text-slate-400" title="This appointment was completed without a treatment record.">No record</span>
                                    @if ($canWriteRecords)
                                        <form action="{{ route('appointments.status', $a) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="status" value="confirmed" />
                                            <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 transition" title="Reopen so a treatment record can be filed">
                                                <i class="fa-solid fa-rotate-left mr-1 text-[10px]"></i>Reopen
                                            </button>
                                        </form>
                                    @endif
                                @endif
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

<x-modal name="appointment-confirm" title="Confirm Appointment" max-width="2xl">
    @include('appointments._confirm-dialog')
</x-modal>

@if ($canWriteRecords)
    <x-modal name="appointment-complete" title="Complete Appointment" max-width="3xl">
        @include('appointments._complete-dialog')
    </x-modal>
@endif

@include('appointments._cancel-dialog')
@endsection
