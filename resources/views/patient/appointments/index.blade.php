@extends('layouts.patient')
@section('page_title', 'My Appointments')

@section('content')
@php
    $colors = ['pending' => 'bg-amber-100 text-amber-700', 'confirmed' => 'bg-emerald-100 text-emerald-700', 'completed' => 'bg-blue-100 text-blue-700', 'cancelled' => 'bg-red-100 text-red-700'];
    $tabs = ['upcoming' => 'Upcoming', 'pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp
<div class="page-header">
    <div><h1 class="page-title">My Appointments</h1><p class="page-subtitle">Track requests, confirmed visits, and completed appointments.</p></div>
    <a href="{{ route('patient.appointments.create') }}" class="primary-action"><i class="fa-solid fa-calendar-plus"></i> Book appointment</a>
</div>

<div class="mb-5 flex flex-wrap gap-2">
    @foreach($tabs as $key => $label)
        <a href="{{ route('patient.appointments.index', ['status' => $key]) }}" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold {{ $status === $key ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="responsive-card overflow-hidden">
    <table class="responsive-stack-table appointment-table w-full text-sm">
        <thead><tr class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><th class="px-5 py-3.5 text-left font-semibold">Service</th><th class="px-5 py-3.5 text-left font-semibold">Date & Time</th><th class="px-5 py-3.5 text-left font-semibold">Dentist</th><th class="px-5 py-3.5 text-left font-semibold">Status</th><th class="px-5 py-3.5 text-right font-semibold">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($appointments as $appointment)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $appointment->service_names }}</td>
                    <td class="px-5 py-4 text-slate-600">@if($appointment->scheduled_start_at){{ $appointment->scheduled_start_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }}@elseif($appointment->requested_start_at)Requested {{ $appointment->requested_start_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }}@else{{ $appointment->appointment_date->format('M j, Y') }}@endif</td>
                    <td class="px-5 py-4 text-slate-600">{{ $appointment->dentist->name ?? 'To be assigned' }}</td>
                    <td class="px-5 py-4"><span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $colors[$appointment->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($appointment->status) }}</span></td>
                    <td class="px-5 py-4 text-right"><a href="{{ route('patient.appointments.show', $appointment) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No appointments in this view.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($appointments->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $appointments->links() }}</div>@endif
</div>
@endsection
