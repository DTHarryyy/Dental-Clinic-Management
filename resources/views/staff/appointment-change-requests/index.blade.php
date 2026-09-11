@extends('layouts.app')
@section('page_title', 'Appointment Change Requests')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">Appointment Change Requests</h1><p class="page-subtitle">Approve or reject patient cancellation and reschedule requests.</p></div>
</div>
<form method="GET" class="filter-bar filter-controls"><select name="status" class="filter-control" onchange="this.form.submit()"><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Approved</option><option value="rejected" @selected($status === 'rejected')>Rejected</option></select></form>
<div class="responsive-card overflow-hidden">
    <table class="responsive-stack-table appointment-table w-full text-sm">
        <thead><tr class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><th class="px-5 py-3.5 text-left">Patient</th><th class="px-5 py-3.5 text-left">Request</th><th class="px-5 py-3.5 text-left">Schedule</th><th class="px-5 py-3.5 text-left">Status</th><th class="px-5 py-3.5 text-right">Action</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($requests as $change)
                <tr class="hover:bg-slate-50"><td class="px-5 py-4 font-semibold text-slate-800">{{ $change->patient->name }}</td><td class="px-5 py-4"><p class="font-semibold text-slate-700">{{ ucfirst($change->type) }} · {{ $change->appointment->service_names }}</p><p class="text-xs text-slate-500">{{ $change->reason }}</p></td><td class="px-5 py-4 text-slate-600">@if($change->proposed_start_at){{ $change->proposed_start_at->setTimezone('Asia/Manila')->format('M j, Y g:i A') }}@else{{ $change->original_start_at?->setTimezone('Asia/Manila')->format('M j, Y g:i A') ?? 'No schedule' }}@endif</td><td class="px-5 py-4"><span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $change->status === 'pending' ? 'bg-amber-100 text-amber-700' : ($change->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700') }}">{{ ucfirst($change->status) }}</span></td><td class="px-5 py-4 text-right">@if($change->status === 'pending')<button type="button" onclick='window.dispatchEvent(new CustomEvent("open-dialog", { detail: { id: "resolve-change-{{ $change->id }}" } }))' class="inline-flex min-h-9 items-center rounded-lg bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Resolve</button>@else<span class="text-xs text-slate-400">{{ $change->resolver->name ?? 'Resolved' }}</span>@endif</td></tr>
            @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No appointment change requests.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($requests->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $requests->links() }}</div>@endif
</div>

@foreach($requests->where('status', 'pending') as $change)
    <x-modal name="resolve-change-{{ $change->id }}" title="Resolve Request" max-width="lg">
        <form action="{{ route('appointment-change-requests.resolve', $change) }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm"><p class="font-semibold text-slate-800">{{ ucfirst($change->type) }} request</p><p class="text-slate-500">{{ $change->reason }}</p></div>
            <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Decision</label><select name="decision" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"><option value="approve">Approve</option><option value="reject">Reject</option></select></div>
            <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Resolution note</label><textarea name="resolution_note" rows="3" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></textarea></div>
            <div class="flex justify-end gap-3 border-t pt-4"><button type="button" x-on:click="open=false" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold">Close</button><button class="primary-action"><i class="fa-solid fa-check"></i> Save decision</button></div>
        </form>
    </x-modal>
@endforeach
@endsection
