@extends('layouts.patient')
@section('page_title', $invoice->invoice_number)

@section('content')
<div class="page-header">
    <div><h1 class="page-title">{{ $invoice->invoice_number }}</h1><p class="page-subtitle">{{ $invoice->display_status }} · {{ $invoice->invoice_date->format('M j, Y') }}</p></div>
    <a href="{{ route('patient.billing.receipt', $invoice) }}" class="primary-action"><i class="fa-solid fa-print"></i> View document</a>
</div>
<div class="responsive-card responsive-card-padding">
    <h2 class="font-bold text-slate-800">Invoice items</h2>
    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
        <table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-4 py-2.5 text-left">Description</th><th class="px-3 py-2.5 text-center">Qty</th><th class="px-4 py-2.5 text-right">Amount</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($invoice->items as $item)<tr><td class="px-4 py-3 text-slate-700">{{ $item->description }}</td><td class="px-3 py-3 text-center">{{ $item->qty }}</td><td class="px-4 py-3 text-right font-medium">PHP {{ number_format($item->qty * $item->price, 2) }}</td></tr>@endforeach</tbody></table>
    </div>
    <div class="mt-4 space-y-2 text-sm"><div class="flex justify-between text-slate-500"><span>Subtotal</span><span>PHP {{ number_format($invoice->subtotal, 2) }}</span></div><div class="flex justify-between text-slate-500"><span>Discount</span><span>PHP {{ number_format($invoice->discount, 2) }}</span></div><div class="flex justify-between border-t pt-2 font-bold text-slate-800"><span>Total</span><span>PHP {{ number_format($invoice->total, 2) }}</span></div><div class="flex justify-between text-emerald-700"><span>Paid</span><span>PHP {{ number_format($invoice->amount_paid, 2) }}</span></div><div class="flex justify-between text-base font-bold text-slate-900"><span>Balance</span><span>PHP {{ number_format($invoice->balance, 2) }}</span></div></div>
</div>
@endsection
