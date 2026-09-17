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

@php
    $pendingPayments = $invoice->payments->where('status', \App\Enums\PaymentStatus::Pending);
@endphp

@if ($pendingPayments->isNotEmpty())
    <div class="responsive-card responsive-card-padding mt-6 border-amber-200 bg-amber-50">
        <h2 class="font-bold text-amber-800"><i class="fa-solid fa-hourglass-half mr-1.5"></i>Payment awaiting verification</h2>
        @foreach ($pendingPayments as $payment)
            <p class="mt-2 text-sm text-amber-800">
                PHP {{ number_format($payment->amount, 2) }} via {{ $payment->methodLabel() }}
                (Ref: {{ $payment->reference }}) submitted {{ $payment->created_at->diffForHumans() }}.
                Your balance will update once our staff confirms it.
            </p>
        @endforeach
    </div>
@elseif ($invoice->payment_status !== 'paid')
    <div class="responsive-card responsive-card-padding mt-6" x-data="{ method: '' }">
        <h2 class="font-bold text-slate-800">Submit a payment</h2>
        <p class="mt-1 text-sm text-slate-500">Already sent your payment? Record it here so we can verify it and update your balance.</p>

        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach (array_merge($inPersonMethods, $submittableMethods) as $m)
                <button type="button" x-on:click="method = '{{ $m->value }}'"
                        :class="method === '{{ $m->value }}' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600'"
                        class="rounded-xl border px-3 py-3 text-xs font-semibold transition">
                    <i class="fa-solid {{ $m->icon() }} mb-1 block text-base"></i>{{ $m->label() }}
                </button>
            @endforeach
        </div>

        {{-- In-person-only methods: instructions, no submission form. --}}
        @foreach ($inPersonMethods as $m)
            <div x-show="method === '{{ $m->value }}'" x-cloak
                 class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                <i class="fa-solid fa-circle-info mr-1.5 text-slate-400"></i>{{ $m->helpText() }}
            </div>
        @endforeach

        {{-- Submittable methods: clinic receiving details + the submission form. --}}
        <form action="{{ route('patient.billing.payments.store', $invoice) }}" method="POST" enctype="multipart/form-data"
              x-show="{{ $submittableMethods ? collect($submittableMethods)->map(fn ($m) => "method === '{$m->value}'")->implode(' || ') : 'false' }}"
              x-cloak class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="method" :value="method">

            @foreach ($submittableMethods as $m)
                @php $channel = $paymentChannels->get($m->value); @endphp
                <div x-show="method === '{{ $m->value }}'" x-cloak class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm text-emerald-900">{{ $m->helpText() }}</p>
                    @if ($channel)
                        <div class="mt-3 flex items-start gap-4">
                            <div class="text-sm">
                                @if ($channel->bank_name)<div class="font-semibold text-emerald-900">{{ $channel->bank_name }}</div>@endif
                                <div class="text-emerald-900">{{ $channel->account_name }}</div>
                                <div class="font-mono text-base font-bold text-emerald-800">{{ $channel->account_number }}</div>
                                @if ($channel->instructions)<p class="mt-1 text-xs text-emerald-700">{{ $channel->instructions }}</p>@endif
                            </div>
                            @if ($channel->qr_url)
                                <img src="{{ $channel->qr_url }}" alt="{{ $m->label() }} QR code" class="h-28 w-28 rounded-lg border border-emerald-200 bg-white object-contain p-1">
                            @endif
                        </div>
                    @else
                        <p class="mt-2 text-xs text-emerald-700">Please contact the clinic for {{ $m->label() }} payment details before submitting.</p>
                    @endif
                </div>
            @endforeach

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Amount</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->submittable_balance }}" value="{{ old('amount', $invoice->submittable_balance) }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Reference number</label>
                    <input type="text" name="reference" maxlength="255" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Date &amp; time paid</label>
                    <input type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Proof of payment <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="file" name="proof" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Notes <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea name="notes" rows="2" maxlength="1000" class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm"></textarea>
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-600">
                Submit for verification
            </button>
        </form>
    </div>
@endif
@endsection
