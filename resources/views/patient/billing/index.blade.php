@extends('layouts.patient')
@section('page_title', 'Billing')

@section('content')
@php $colors = ['paid' => 'bg-emerald-100 text-emerald-700', 'partial' => 'bg-blue-100 text-blue-700', 'unpaid' => 'bg-amber-100 text-amber-700', 'Overdue' => 'bg-red-100 text-red-700']; @endphp
<div class="page-header"><div><h1 class="page-title">Billing</h1><p class="page-subtitle">View balances, payment history, invoices, and receipts.</p></div></div>
<div class="mb-6 grid gap-3 sm:grid-cols-3">
    @foreach([
        ['label' => 'Outstanding', 'value' => $summary['outstanding'], 'class' => 'bg-red-50 text-red-600'],
        ['label' => 'Paid', 'value' => $summary['paid'], 'class' => 'bg-emerald-50 text-emerald-600'],
        ['label' => 'Overdue', 'value' => $summary['overdue'], 'class' => 'bg-amber-50 text-amber-700'],
    ] as $card)
        <div class="responsive-card p-4"><p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p><p class="mt-2 text-xl font-bold {{ $card['class'] }} inline-block rounded-xl px-3 py-1">PHP {{ number_format($card['value'], 2) }}</p></div>
    @endforeach
</div>
<form method="GET" class="filter-bar filter-controls">
    <select name="status" class="filter-control" onchange="this.form.submit()"><option value="all" @selected($status === 'all')>All Status</option><option value="unpaid" @selected($status === 'unpaid')>Unpaid</option><option value="partial" @selected($status === 'partial')>Partial</option><option value="paid" @selected($status === 'paid')>Paid</option></select>
</form>
<div class="responsive-card overflow-hidden">
    <table class="responsive-stack-table billing-table w-full text-sm">
        <thead><tr class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><th class="px-5 py-3.5 text-left">Invoice</th><th class="px-5 py-3.5 text-left">Date</th><th class="px-5 py-3.5 text-right">Total</th><th class="px-5 py-3.5 text-left">Status</th><th class="px-5 py-3.5 text-right">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($invoices as $invoice)
                <tr class="hover:bg-slate-50"><td class="px-5 py-4 font-mono font-bold text-slate-800">{{ $invoice->invoice_number }}</td><td class="px-5 py-4 text-slate-600">{{ $invoice->invoice_date->format('M j, Y') }}</td><td class="px-5 py-4 text-right font-bold text-slate-800">PHP {{ number_format($invoice->total, 2) }}</td><td class="px-5 py-4"><span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $colors[$invoice->display_status] ?? $colors[$invoice->payment_status] ?? 'bg-slate-100 text-slate-600' }}">{{ $invoice->display_status }}</span></td><td class="px-5 py-4 text-right"><a href="{{ route('patient.billing.show', $invoice) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">View</a></td></tr>
            @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No billing documents yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($invoices->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $invoices->links() }}</div>@endif
</div>
@endsection
