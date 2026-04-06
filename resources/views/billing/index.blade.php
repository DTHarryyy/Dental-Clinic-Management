@extends('layouts.app')
@section('page_title', 'Billing')

@section('content')
@php
    $invoices = [
        ['id' => 89,  'patient' => 'Maria Santos',     'service' => 'Teeth Cleaning',   'date' => 'Mar 22, 2026', 'amount' => '₱800',    'status' => 'Paid'],
        ['id' => 88,  'patient' => 'Jose Dela Cruz',   'service' => 'Root Canal',        'date' => 'Mar 21, 2026', 'amount' => '₱8,500',  'status' => 'Paid'],
        ['id' => 87,  'patient' => 'Ana Reyes',        'service' => 'Dental Filling',    'date' => 'Mar 18, 2026', 'amount' => '₱1,500',  'status' => 'Unpaid'],
        ['id' => 86,  'patient' => 'Luis Gomez',       'service' => 'Tooth Extraction',  'date' => 'Mar 15, 2026', 'amount' => '₱2,000',  'status' => 'Paid'],
        ['id' => 85,  'patient' => 'Rosa Bautista',    'service' => 'Orthodontic Check', 'date' => 'Mar 10, 2026', 'amount' => '₱500',    'status' => 'Unpaid'],
        ['id' => 84,  'patient' => 'Carlos Mendoza',   'service' => 'Consultation',      'date' => 'Mar 5, 2026',  'amount' => '₱300',    'status' => 'Paid'],
        ['id' => 83,  'patient' => 'Elena Villanueva', 'service' => 'Teeth Whitening',   'date' => 'Feb 28, 2026', 'amount' => '₱5,000',  'status' => 'Overdue'],
        ['id' => 82,  'patient' => 'Ricardo Torres',   'service' => 'Dentures',          'date' => 'Feb 20, 2026', 'amount' => '₱15,000', 'status' => 'Partial'],
    ];

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
    <a href="/billing/create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-plus"></i> New Invoice
    </a>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @php
        $summary = [
            ['label' => 'Total Billed',  'value' => '₱33,600', 'bg' => 'bg-slate-50',   'color' => 'text-slate-600'],
            ['label' => 'Paid',          'value' => '₱11,600', 'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Unpaid',        'value' => '₱2,000',  'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
            ['label' => 'Overdue',       'value' => '₱5,000',  'bg' => 'bg-red-50',     'color' => 'text-red-600'],
        ];
    @endphp
    @foreach ($summary as $s)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs text-slate-500 font-medium mb-1">{{ $s['label'] }}</div>
            <div class="font-bold text-lg {{ $s['color'] }}">{{ $s['value'] }}</div>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" placeholder="Search invoice or patient..." class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option>All Status</option>
        <option>Paid</option>
        <option>Unpaid</option>
        <option>Overdue</option>
        <option>Partial</option>
    </select>
    <input type="month" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
</div>

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
            @foreach ($invoices as $inv)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4 font-mono text-xs text-slate-500">INV-{{ str_pad($inv['id'], 4, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $inv['patient'] }}</td>
                    <td class="px-5 py-4 text-slate-600 hidden sm:table-cell">{{ $inv['service'] }}</td>
                    <td class="px-5 py-4 text-slate-500 hidden md:table-cell">{{ $inv['date'] }}</td>
                    <td class="px-5 py-4 font-bold text-slate-800 text-right">{{ $inv['amount'] }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $statusColors[$inv['status']] }}">
                            {{ $inv['status'] }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="/billing/{{ $inv['id'] }}/receipt" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Receipt</a>
                            @if ($inv['status'] !== 'Paid')
                                <button class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition">Mark Paid</button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
        <span>Showing 1–8 of 8 invoices</span>
        <div class="flex items-center gap-1">
            <button class="px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold">1</button>
        </div>
    </div>
</div>
@endsection
