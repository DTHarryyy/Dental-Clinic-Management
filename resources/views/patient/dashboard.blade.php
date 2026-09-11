@extends('layouts.patient')
@section('page_title', 'Patient Dashboard')

@section('content')
@php
    $dashboardCards = [
        ['label' => 'Next appointment', 'value' => $nextAppointment ? $nextAppointment->scheduled_start_at->setTimezone('Asia/Manila')->format('M j') : 'None', 'icon' => 'fa-calendar-check', 'class' => 'bg-emerald-50 text-emerald-600'],
        ['label' => 'Pending requests', 'value' => $pendingCount, 'icon' => 'fa-hourglass-half', 'class' => 'bg-amber-50 text-amber-600'],
        ['label' => 'Outstanding balance', 'value' => 'PHP '.number_format($outstandingBalance, 2), 'icon' => 'fa-credit-card', 'class' => 'bg-red-50 text-red-600'],
        ['label' => 'Unread alerts', 'value' => $unreadCount, 'icon' => 'fa-bell', 'class' => 'bg-blue-50 text-blue-600'],
    ];
@endphp
<div class="page-header">
    <div><h1 class="page-title">Patient Dashboard</h1><p class="page-subtitle">Welcome back, {{ $patient->first_name }}.</p></div>
    <a href="{{ route('patient.appointments.create') }}" class="primary-action"><i class="fa-solid fa-calendar-plus"></i> Book appointment</a>
</div>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach($dashboardCards as $card)
        <div class="responsive-card p-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['class'] }}"><i class="fa-solid {{ $card['icon'] }}"></i></span><div class="min-w-0"><p class="truncate text-xs font-semibold text-slate-500">{{ $card['label'] }}</p><p class="truncate text-lg font-bold text-slate-800">{{ $card['value'] }}</p></div></div></div>
    @endforeach
</div>

<div class="grid gap-5 lg:grid-cols-2">
    <section class="responsive-card responsive-card-padding">
        <h2 class="font-bold text-slate-800">Upcoming appointment</h2>
        @if($nextAppointment)
            <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                <p class="font-semibold text-emerald-800">{{ $nextAppointment->service_names }}</p>
                <p class="mt-1 text-sm text-emerald-700">{{ $nextAppointment->scheduled_start_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} @if($nextAppointment->dentist)with {{ $nextAppointment->dentist->name }}@endif</p>
            </div>
        @else
            <div class="mt-4 rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">No confirmed appointment yet.</div>
        @endif
    </section>
    <section class="responsive-card responsive-card-padding">
        <h2 class="font-bold text-slate-800">Latest treatment summary</h2>
        @if($latestSummary)
            <a href="{{ route('patient.treatments.show', $latestSummary) }}" class="mt-4 block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:border-emerald-200">
                <p class="font-semibold text-slate-800">{{ $latestSummary->procedure }}</p>
                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $latestSummary->patient_summary }}</p>
            </a>
        @else
            <div class="mt-4 rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">No published summaries yet.</div>
        @endif
    </section>
    <section class="responsive-card responsive-card-padding">
        <h2 class="font-bold text-slate-800">Recent appointments</h2>
        <div class="mt-4 space-y-3">
            @forelse($recentAppointments as $appointment)
                <a href="{{ route('patient.appointments.show', $appointment) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"><span><span class="block text-sm font-semibold text-slate-800">{{ $appointment->service_names }}</span><span class="text-xs text-slate-500">{{ ucfirst($appointment->status) }}</span></span><i class="fa-solid fa-chevron-right text-slate-300"></i></a>
            @empty
                <p class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">No appointments yet.</p>
            @endforelse
        </div>
    </section>
    <section class="responsive-card responsive-card-padding">
        <h2 class="font-bold text-slate-800">Recent billing</h2>
        <div class="mt-4 space-y-3">
            @forelse($recentInvoices as $invoice)
                <a href="{{ route('patient.billing.show', $invoice) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"><span><span class="block text-sm font-semibold text-slate-800">{{ $invoice->invoice_number }}</span><span class="text-xs text-slate-500">{{ $invoice->display_status }}</span></span><span class="text-sm font-bold text-slate-800">PHP {{ number_format($invoice->total, 2) }}</span></a>
            @empty
                <p class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">No invoices yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
