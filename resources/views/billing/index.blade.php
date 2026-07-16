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

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Billing</h1>
        <p class="text-slate-500 text-sm mt-0.5">Invoices and payment records</p>
    </div>
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'invoice-create' } }))" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-plus"></i> New Invoice
    </button>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['label' => 'Total Billed',  'value' => $summary['total'],   'bg' => 'bg-slate-50',   'color' => 'text-slate-600'],
            ['label' => 'Paid',          'value' => $summary['paid'],    'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Unpaid',        'value' => $summary['unpaid'],  'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
            ['label' => 'Overdue',       'value' => $summary['overdue'], 'bg' => 'bg-red-50',     'color' => 'text-red-600'],
        ];
    @endphp
    @foreach ($cards as $s)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs text-slate-500 font-medium mb-1">{{ $s['label'] }}</div>
            <div class="font-bold text-lg {{ $s['color'] }}">₱{{ number_format($s['value'], 2) }}</div>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patient..." class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option {{ !request('status') ? 'selected' : '' }}>All Status</option>
        <option {{ request('status') === 'Paid' ? 'selected' : '' }}>Paid</option>
        <option {{ request('status') === 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
        <option {{ request('status') === 'Partial' ? 'selected' : '' }}>Partial</option>
    </select>
    <input type="month" name="month" value="{{ request('month') }}" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold">Search</button>
</form>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
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
                    <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $inv->invoice_number }}</td>
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
                            <a href="{{ route('billing.receipt', $inv) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Receipt</a>
                            @if ($inv->payment_status !== 'paid')
                                <form action="{{ route('billing.mark-paid', $inv) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition">Mark Paid</button>
                                </form>
                            @endif
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

<x-modal name="patient-create" title="Add New Patient" max-width="4xl">
    @include('patients._form-dialog', ['patient' => null])
</x-modal>
@endsection
