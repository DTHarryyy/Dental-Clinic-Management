@extends('layouts.app')
@section('page_title', 'Billing')

@section('content')
@php
    $statusColors = [
        'Paid'    => 'bg-emerald-100 text-emerald-700',
        'Unpaid'  => 'bg-amber-100 text-amber-700',
        'Overdue' => 'bg-red-100 text-red-700',
        'Partial' => 'bg-blue-100 text-blue-700',
    ];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Billing</h1>
        <p class="text-slate-500 text-sm mt-0.5">Invoices and payment records</p>
    </div>
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'invoice-create' } }))" class="primary-action">
        <i class="fa-solid fa-plus"></i> New Invoice
    </button>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    @php
        $cards = [
            ['label' => 'Total Billed',  'value' => $summary['total'],   'bg' => 'bg-slate-50',   'color' => 'text-slate-600'],
            ['label' => 'Paid',          'value' => $summary['paid'],    'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Unpaid',        'value' => $summary['unpaid'],  'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
            ['label' => 'Overdue',       'value' => $summary['overdue'], 'bg' => 'bg-red-50',     'color' => 'text-red-600'],
        ];
    @endphp
    @foreach ($cards as $s)
        <div class="min-w-0 bg-white rounded-2xl border border-slate-200 shadow-sm p-3 sm:p-4">
            <div class="text-xs text-slate-500 font-medium mb-1">{{ $s['label'] }}</div>
            <div class="break-content font-bold text-base sm:text-lg {{ $s['color'] }}">₱{{ number_format($s['value'], 2) }}</div>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" data-auto-filter="billing" class="filter-bar filter-controls">
    <div class="relative min-w-0 flex-1">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patient..." autocomplete="off" class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <select name="status" class="filter-control">
        <option {{ !request('status') ? 'selected' : '' }}>All Status</option>
        <option {{ request('status') === 'Paid' ? 'selected' : '' }}>Paid</option>
        <option {{ request('status') === 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
        <option {{ request('status') === 'Partial' ? 'selected' : '' }}>Partial</option>
    </select>
    <input type="month" name="month" value="{{ request('month') }}" class="filter-control" />
</form>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    @if ($viewInvoiceId)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <span><i class="fa-solid fa-magnifying-glass mr-1.5"></i>Showing the invoice selected from global search.</span>
            <a href="{{ route('billing.index') }}" class="font-semibold hover:underline">Clear selected result</a>
        </div>
    @endif
    <table class="responsive-stack-table billing-table w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <th class="text-left px-5 py-3.5 font-semibold">Invoice</th>
                <th class="text-left px-5 py-3.5 font-semibold">Patient</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden sm:table-cell">Service</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden md:table-cell">Date</th>
                <th class="text-right px-5 py-3.5 font-semibold">Amount</th>
                <th class="text-left px-5 py-3.5 font-semibold">Status</th>
                <th class="text-right px-5 py-3.5 font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($invoices as $inv)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        <button type="button" data-invoice-details-url="{{ route('billing.details', $inv) }}" class="font-mono text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">{{ $inv->invoice_number }}</button>
                    </td>
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $inv->patient->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-600 hidden sm:table-cell">{{ $inv->items->first()->description ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-500 hidden md:table-cell">{{ $inv->invoice_date->format('M j, Y') }}</td>
                    <td class="px-5 py-4 font-bold text-slate-800 text-right">₱{{ number_format($inv->total, 2) }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $statusColors[$inv->display_status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $inv->display_status }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" data-invoice-details-url="{{ route('billing.details', $inv) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">{{ $inv->payment_status === 'paid' ? 'Receipt' : 'Invoice' }}</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-slate-400">No invoices yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($invoices->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $invoices->links() }}
        </div>
    @endif
</div>

<x-modal name="invoice-create" title="Create Invoice" max-width="5xl">
    @include('billing._form-dialog')
</x-modal>

<x-modal name="invoice-details" title="Invoice Details" max-width="4xl" body-class="overflow-y-auto p-0">
    <div data-invoice-details-body class="min-h-64"></div>
</x-modal>

<x-modal name="receipt-preview" title="Receipt Preview" max-width="5xl" body-class="flex min-h-0 flex-col overflow-hidden p-0">
    <div class="flex shrink-0 items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-3">
        <p class="text-xs text-slate-500">Review the document before printing.</p>
        <button type="button" data-print-receipt-preview disabled class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900 disabled:cursor-wait disabled:opacity-60"><i class="fa-solid fa-print mr-1.5" aria-hidden="true"></i><span data-preview-print-label>Loading…</span></button>
    </div>
    <div class="relative min-h-0 flex-1 bg-slate-100">
        <div data-receipt-preview-loading role="status" class="absolute inset-0 z-10 flex items-center justify-center bg-white px-6 text-center text-sm text-slate-500">Loading receipt…</div>
        <iframe data-receipt-preview-frame title="Receipt print preview" class="h-[72vh] w-full border-0 bg-white"></iframe>
    </div>
</x-modal>

<x-modal name="patient-create" title="Add New Patient" max-width="3xl" body-class="flex flex-col min-h-0">
    @include('patients._form-dialog', ['patient' => null])
</x-modal>
@endsection

@push('scripts')
<script src="{{ asset('js/billing-details.js') }}" defer></script>
@if ($viewInvoiceId)
<script>
    document.addEventListener('turbo:load', () => document.querySelector('[data-invoice-details-url="{{ route('billing.details', $viewInvoiceId) }}"]')?.click(), { once: true });
</script>
@endif
@endpush
