@extends('layouts.patient')
@section('page_title', 'Appointment Details')

@section('content')
@php $colors = ['pending' => 'bg-amber-100 text-amber-700', 'confirmed' => 'bg-emerald-100 text-emerald-700', 'completed' => 'bg-blue-100 text-blue-700', 'cancelled' => 'bg-red-100 text-red-700']; @endphp
<div class="page-header">
    <div><h1 class="page-title">Appointment Details</h1><p class="page-subtitle">{{ $appointment->service_names }}</p></div>
    <a href="{{ route('patient.appointments.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-2"></i>Back</a>
</div>

<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <section class="responsive-card responsive-card-padding">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
            <div><h2 class="font-bold text-slate-800">{{ $appointment->service_names }}</h2><p class="mt-1 text-sm text-slate-500">Appointment #{{ str_pad($appointment->id, 4, '0', STR_PAD_LEFT) }}</p></div>
            <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $colors[$appointment->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($appointment->status) }}</span>
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Requested schedule</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $appointment->requested_start_at?->setTimezone('Asia/Manila')->format('M j, Y g:i A') ?? $appointment->appointment_date->format('M j, Y') }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Confirmed schedule</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $appointment->scheduled_start_at?->setTimezone('Asia/Manila')->format('M j, Y g:i A') ?? 'Pending confirmation' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dentist</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $appointment->dentist->name ?? 'To be assigned' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Estimated amount</dt><dd class="mt-1 text-sm font-semibold text-slate-800">PHP {{ number_format($appointment->estimated_total, 2) }}</dd></div>
        </dl>
        @if($appointment->concern)<div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Concern</p><p class="mt-2 text-sm leading-6 text-slate-700">{{ $appointment->concern }}</p></div>@endif
    </section>

    <aside class="space-y-5">
        <section class="responsive-card responsive-card-padding">
            <h2 class="font-bold text-slate-800">Patient actions</h2>
            @if($appointment->status === 'pending')
                <form action="{{ route('patient.appointments.withdraw', $appointment) }}" method="POST" class="mt-4">@csrf @method('PATCH')<button class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-red-50 px-4 text-sm font-semibold text-red-600 hover:bg-red-100"><i class="fa-solid fa-ban"></i> Withdraw request</button></form>
            @elseif($appointment->status === 'confirmed' && ! $appointment->changeRequests->where('status', 'pending')->count())
                <div class="mt-4 grid gap-3">
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'change-cancel' } }))" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-red-50 px-4 text-sm font-semibold text-red-600 hover:bg-red-100"><i class="fa-solid fa-calendar-xmark"></i> Request cancellation</button>
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'change-reschedule' } }))" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-calendar-days"></i> Request reschedule</button>
                </div>
            @elseif($appointment->changeRequests->where('status', 'pending')->count())
                <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">A change request is waiting for staff review.</p>
            @else
                <p class="mt-3 text-sm text-slate-500">This appointment is read-only.</p>
            @endif
        </section>
        <section class="responsive-card responsive-card-padding">
            <h2 class="font-bold text-slate-800">Requests</h2>
            <div class="mt-3 space-y-3">
                @forelse($appointment->changeRequests as $change)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm"><div class="flex justify-between"><span class="font-semibold text-slate-800">{{ ucfirst($change->type) }}</span><span class="text-xs font-semibold text-slate-500">{{ ucfirst($change->status) }}</span></div><p class="mt-1 text-xs text-slate-500">{{ $change->reason }}</p></div>
                @empty
                    <p class="text-sm text-slate-500">No change requests.</p>
                @endforelse
            </div>
        </section>
    </aside>
</div>

<x-modal name="change-cancel" title="Request cancellation" max-width="lg">
    <form action="{{ route('patient.appointments.change', $appointment) }}" method="POST" class="space-y-4">@csrf<input type="hidden" name="type" value="cancel"><label class="block text-sm font-medium text-slate-700">Reason</label><textarea name="reason" rows="4" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></textarea><div class="flex justify-end gap-3 border-t pt-4"><button type="button" x-on:click="open=false" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold">Close</button><button class="min-h-11 rounded-xl bg-red-600 px-4 text-sm font-semibold text-white">Submit request</button></div></form>
</x-modal>
<x-modal name="change-reschedule" title="Request reschedule" max-width="lg">
    <form action="{{ route('patient.appointments.change', $appointment) }}" method="POST" class="space-y-4">@csrf<input type="hidden" name="type" value="reschedule"><div><label class="mb-1.5 block text-sm font-medium text-slate-700">Preferred date and time</label><input type="datetime-local" name="proposed_start_at" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div><div><label class="mb-1.5 block text-sm font-medium text-slate-700">Reason</label><textarea name="reason" rows="4" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></textarea></div><div class="flex justify-end gap-3 border-t pt-4"><button type="button" x-on:click="open=false" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold">Close</button><button class="min-h-11 rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-white">Submit request</button></div></form>
</x-modal>
@endsection
