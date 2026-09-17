@extends('layouts.app')
@section('page_title', 'Pending Payments')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pending Payments</h1>
        <p class="text-slate-500 text-sm mt-0.5">Patient-submitted payments awaiting verification before they count toward a balance.</p>
    </div>
    <a href="{{ route('billing.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back to Billing</a>
</div>

<div class="responsive-card overflow-hidden">
    <table class="responsive-stack-table billing-table w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <th class="px-5 py-3.5 text-left">Patient</th>
                <th class="px-5 py-3.5 text-left">Invoice</th>
                <th class="px-5 py-3.5 text-left">Method</th>
                <th class="px-5 py-3.5 text-right">Amount</th>
                <th class="px-5 py-3.5 text-left">Submitted</th>
                <th class="px-5 py-3.5 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($payments as $payment)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $payment->invoice?->patient?->name ?? 'Unknown patient' }}</td>
                    <td class="px-5 py-4">
                        <a href="{{ route('billing.receipt', $payment->invoice) }}" class="font-mono text-xs font-semibold text-emerald-700 hover:underline">{{ $payment->invoice?->invoice_number }}</a>
                    </td>
                    <td class="px-5 py-4 text-slate-700">
                        {{ $payment->methodLabel() }}
                        @if($payment->reference)<span class="block text-xs text-slate-400">Ref: {{ $payment->reference }}</span>@endif
                        @if($payment->hasProof())<a href="{{ route('payments.proof', $payment) }}" target="_blank" class="block text-xs font-semibold text-emerald-600 hover:underline">View proof</a>@endif
                    </td>
                    <td class="px-5 py-4 text-right font-bold text-slate-800">₱{{ number_format($payment->amount, 2) }}</td>
                    <td class="px-5 py-4 text-slate-500">
                        {{ $payment->created_at->format('M j, Y g:i A') }}
                        @if($payment->submitter)<span class="block text-xs text-slate-400">by {{ $payment->submitter->name }}</span>@endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        <button type="button" onclick='window.dispatchEvent(new CustomEvent("open-dialog", { detail: { id: "review-payment-{{ $payment->id }}" } }))' class="inline-flex min-h-9 items-center rounded-lg bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Review</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-400">No payments are awaiting verification.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($payments->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $payments->links() }}
        </div>
    @endif
</div>

@foreach ($payments as $payment)
    <x-modal name="review-payment-{{ $payment->id }}" title="Review Payment" max-width="lg">
        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Patient</span><span class="font-semibold text-slate-800">{{ $payment->invoice?->patient?->name ?? 'Unknown patient' }}</span></div>
                <div class="flex justify-between mt-1"><span class="text-slate-500">Invoice</span><span class="font-semibold text-slate-800">{{ $payment->invoice?->invoice_number }}</span></div>
                <div class="flex justify-between mt-1"><span class="text-slate-500">Method</span><span class="font-semibold text-slate-800">{{ $payment->methodLabel() }}</span></div>
                @if($payment->reference)<div class="flex justify-between mt-1"><span class="text-slate-500">Reference</span><span class="font-semibold text-slate-800">{{ $payment->reference }}</span></div>@endif
                <div class="flex justify-between mt-1"><span class="text-slate-500">Amount</span><span class="font-bold text-emerald-700">₱{{ number_format($payment->amount, 2) }}</span></div>
                @if($payment->notes)<p class="mt-2 text-xs text-slate-600">{{ $payment->notes }}</p>@endif
                @if($payment->hasProof())
                    <a href="{{ route('payments.proof', $payment) }}" target="_blank" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:underline">
                        <i class="fa-solid fa-image"></i> View proof of payment
                    </a>
                @endif
            </div>

            <form action="{{ route('billing.payments.verify', $payment) }}" method="POST">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-600">
                    <i class="fa-solid fa-check mr-1.5"></i>Verify Payment
                </button>
            </form>

            <form action="{{ route('billing.payments.reject', $payment) }}" method="POST" class="space-y-2">
                @csrf
                <label class="block text-xs font-semibold text-slate-600">Rejection reason</label>
                <textarea name="rejection_reason" rows="2" required maxlength="1000" placeholder="Why can't this be verified?" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"></textarea>
                <button type="submit" class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">
                    <i class="fa-solid fa-xmark mr-1.5"></i>Reject Payment
                </button>
            </form>

            <div class="flex justify-end border-t pt-3">
                <button type="button" x-on:click="open=false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Close</button>
            </div>
        </div>
    </x-modal>
@endforeach
@endsection
