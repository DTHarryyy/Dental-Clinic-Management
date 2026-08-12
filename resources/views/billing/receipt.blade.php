@extends('layouts.app')
@section('page_title', $invoice->invoice_number)
@php
    $embedded = request()->boolean('embedded');
@endphp
@section('body_class'){{ $embedded ? 'receipt-embedded' : '' }}@endsection

@push('styles')
<style>
    @media print {
        @page { margin: 12mm; }

        html, body { background: #fff !important; }

        /* The status bar and payment panel carry meaning, so keep their fills on paper. */
        #receipt, #receipt * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #receipt {
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        #app-toast { display: none !important; }
    }
    body.receipt-embedded > .min-h-screen > aside,
    body.receipt-embedded > .min-h-screen > div > header { display: none !important; }
    body.receipt-embedded > .min-h-screen > div > main { padding: 24px !important; }
</style>
@endpush

@section('content')
@php
    $isPaid = $invoice->payment_status === 'paid';
    $isPartial = $invoice->payment_status === 'partial';
    $status = $invoice->display_status;
    $latestPayment = $invoice->payments->sortByDesc('paid_at')->first();

    // An unpaid document is a bill, not proof of payment.
    $docTitle = $isPaid ? 'RECEIPT' : 'INVOICE';

    $themes = [
        'Paid' => [
            'bar' => 'bg-emerald-500', 'muted' => 'text-emerald-100', 'badge' => 'bg-white text-emerald-700',
            'panel' => 'bg-emerald-50 border-emerald-100', 'label' => 'text-emerald-600',
            'value' => 'text-emerald-800', 'icon' => 'bg-emerald-100 text-emerald-600', 'glyph' => 'fa-circle-check',
        ],
        'Unpaid' => [
            'bar' => 'bg-amber-500', 'muted' => 'text-amber-100', 'badge' => 'bg-white text-amber-700',
            'panel' => 'bg-amber-50 border-amber-100', 'label' => 'text-amber-600',
            'value' => 'text-amber-800', 'icon' => 'bg-amber-100 text-amber-600', 'glyph' => 'fa-hourglass-half',
        ],
        'Overdue' => [
            'bar' => 'bg-red-500', 'muted' => 'text-red-100', 'badge' => 'bg-white text-red-700',
            'panel' => 'bg-red-50 border-red-100', 'label' => 'text-red-600',
            'value' => 'text-red-800', 'icon' => 'bg-red-100 text-red-600', 'glyph' => 'fa-triangle-exclamation',
        ],
        'Partial' => [
            'bar' => 'bg-blue-500', 'muted' => 'text-blue-100', 'badge' => 'bg-white text-blue-700',
            'panel' => 'bg-blue-50 border-blue-100', 'label' => 'text-blue-600',
            'value' => 'text-blue-800', 'icon' => 'bg-blue-100 text-blue-600', 'glyph' => 'fa-circle-half-stroke',
        ],
    ];
    $t = $themes[$status] ?? $themes['Unpaid'];
@endphp

@unless($embedded)
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5 print:hidden">
    <a href="{{ route('billing.index') }}" class="hover:text-emerald-600 transition">Billing</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">{{ $invoice->invoice_number }}</span>
</div>
@endunless

@unless($embedded)
<div class="flex flex-wrap items-center justify-between gap-4 mb-6 print:hidden">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ ucfirst(strtolower($docTitle)) }}</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ $invoice->invoice_number }} · {{ $invoice->patient->name ?? 'Unknown patient' }}</p>
    </div>
    <div class="flex gap-2">
        @unless ($isPaid)
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'payment-record' } }))" class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                <i class="fa-solid fa-plus mr-1"></i> Record Payment
            </button>
        @endunless
        <form action="{{ route('billing.send', $invoice) }}" method="POST" data-turbo="false" data-turbo-prefetch="false">
            @csrf
            <button type="submit" class="px-4 py-2.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 font-semibold text-sm transition">
                <i class="fa-solid fa-envelope mr-1"></i> Send {{ $isPaid ? 'Receipt' : 'Invoice' }} Again
            </button>
        </form>
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
            <i class="fa-solid fa-print mr-1"></i> Print
        </button>
    </div>
</div>
@endunless

<div class="max-w-2xl print:max-w-none {{ $embedded ? 'mx-auto' : '' }}">
    @if (! $invoice->patient?->email)
        <div class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700 print:hidden">Add a valid email to this patient before sending billing documents.</div>
    @endif
    @if (! $clinic->address && ! $clinic->phone && ! $clinic->email)
        <div class="mb-4 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-2 print:hidden">
            <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
            <span>Your clinic address and contact details are empty, so they will not appear on the printed receipt. Add them in <a href="{{ route('settings.clinic') }}" class="font-semibold underline hover:text-amber-900">Settings → Clinic</a>.</span>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" id="receipt">

        {{-- Header --}}
        <div class="{{ $t['bar'] }} text-white px-8 py-8">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0"><i class="fa-solid fa-tooth text-xl"></i></div>
                        <div class="font-bold text-lg">{{ $clinic->clinic_name ?: 'DentalCare' }}</div>
                    </div>
                    @if ($clinic->address)
                        <div class="{{ $t['muted'] }} text-xs mt-2">{{ $clinic->address }}</div>
                    @endif
                    @if ($clinic->phone)
                        <div class="{{ $t['muted'] }} text-xs">{{ $clinic->phone }}</div>
                    @endif
                    @if ($clinic->tax_id)
                        <div class="{{ $t['muted'] }} text-xs">TIN: {{ $clinic->tax_id }}</div>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <div class="text-2xl font-bold tracking-wide">{{ $docTitle }}</div>
                    <div class="{{ $t['muted'] }} text-sm mt-1 font-mono">{{ $invoice->invoice_number }}</div>
                    <div class="mt-3 inline-block {{ $t['badge'] }} text-xs font-bold px-3 py-1 rounded-lg uppercase tracking-wide">{{ $status }}</div>
                </div>
            </div>
        </div>

        <div class="px-8 py-6">

            {{-- Dates + Patient --}}
            <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-slate-100">
                <div>
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Billed To</div>
                    <div class="font-semibold text-slate-800">{{ $invoice->patient->name ?? '—' }}</div>
                    <div class="text-sm text-slate-500 mt-0.5">{{ $invoice->patient->mobile ?? '—' }}</div>
                    <div class="text-xs text-slate-400 mt-0.5">Patient #{{ $invoice->patient ? str_pad($invoice->patient->id, 4, '0', STR_PAD_LEFT) : '—' }}</div>
                </div>
                <div class="text-right space-y-3">
                    <div>
                        <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Invoice Date</div>
                        <div class="font-semibold text-slate-800">{{ $invoice->invoice_date->format('F j, Y') }}</div>
                    </div>
                    @if ($invoice->due_date)
                        <div>
                            <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Due Date</div>
                            <div class="font-semibold {{ $status === 'Overdue' ? 'text-red-600' : 'text-slate-800' }}">{{ $invoice->due_date->format('F j, Y') }}</div>
                        </div>
                    @endif
                    @if ($invoice->dentalRecord?->dentist)
                        <div>
                            <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Dentist</div>
                            <div class="text-sm text-slate-700">{{ $invoice->dentalRecord->dentist->name }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Line items --}}
            <table class="w-full text-sm mb-6">
                <thead>
                    <tr class="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                        <th class="text-left pb-2 font-semibold">Description</th>
                        <th class="text-center pb-2 font-semibold">Qty</th>
                        <th class="text-right pb-2 font-semibold">Price</th>
                        <th class="text-right pb-2 font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->items as $item)
                        <tr class="border-b border-slate-50">
                            <td class="py-3 text-slate-700">{{ $item->description }}</td>
                            <td class="py-3 text-center text-slate-500">{{ $item->qty }}</td>
                            <td class="py-3 text-right text-slate-500">₱{{ number_format($item->price, 2) }}</td>
                            <td class="py-3 text-right font-medium text-slate-800">₱{{ number_format($item->qty * $item->price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">No items on this invoice.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="space-y-2 text-sm border-t border-slate-100 pt-4">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>₱{{ number_format($invoice->subtotal, 2) }}</span></div>
                @if ($invoice->discount > 0)
                    <div class="flex justify-between text-slate-500"><span>Discount</span><span>−₱{{ number_format($invoice->discount, 2) }}</span></div>
                @endif
                <div class="flex justify-between font-bold text-lg text-slate-800 pt-3 border-t border-slate-100">
                    <span>Total</span><span>₱{{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500"><span>Amount Paid</span><span>₱{{ number_format($invoice->amount_paid, 2) }}</span></div>
                <div class="flex justify-between font-bold text-slate-800"><span>Remaining Balance</span><span>₱{{ number_format($invoice->balance, 2) }}</span></div>
            </div>

            {{-- Payment info --}}
            <div class="mt-6 {{ $t['panel'] }} border rounded-xl p-4 flex items-center justify-between gap-4">
                <div>
                    @if ($isPaid)
                        <div class="text-xs {{ $t['label'] }} font-semibold uppercase tracking-wide">Payment Received</div>
                        <div class="font-semibold {{ $t['value'] }} mt-0.5">{{ $latestPayment?->method ?: $invoice->payment_method ?: 'Method not recorded' }} — ₱{{ number_format($invoice->amount_paid, 2) }}</div>
                        <div class="text-xs {{ $t['label'] }}">Paid in full on {{ $invoice->payments->max('paid_at')?->format('F j, Y') }}</div>
                    @elseif ($isPartial)
                        <div class="text-xs {{ $t['label'] }} font-semibold uppercase tracking-wide">Partial Payment</div>
                        <div class="font-semibold {{ $t['value'] }} mt-0.5">{{ $latestPayment?->method ?: $invoice->payment_method ?: 'Method not recorded' }}</div>
                        <div class="text-xs {{ $t['label'] }}">
                            A balance remains on this invoice{{ $invoice->due_date ? ' — due '.$invoice->due_date->format('F j, Y') : '' }}.
                        </div>
                    @else
                        <div class="text-xs {{ $t['label'] }} font-semibold uppercase tracking-wide">Balance Due</div>
                        <div class="font-semibold {{ $t['value'] }} mt-0.5 text-lg">₱{{ number_format($invoice->total, 2) }}</div>
                        <div class="text-xs {{ $t['label'] }}">
                            @if ($status === 'Overdue')
                                Overdue since {{ $invoice->due_date->format('F j, Y') }}
                            @elseif ($invoice->due_date)
                                Payable by {{ $invoice->due_date->format('F j, Y') }}
                            @else
                                No payment recorded yet
                            @endif
                        </div>
                    @endif
                </div>
                <div class="h-12 w-12 rounded-full {{ $t['icon'] }} flex items-center justify-center text-2xl shrink-0"><i class="fa-solid {{ $t['glyph'] }}"></i></div>
            </div>

            {{-- Notes --}}
            @if ($invoice->notes)
                <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Notes</div>
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ $invoice->notes }}</p>
                </div>
            @endif

            @if ($invoice->payments->isNotEmpty())
                <div class="mt-6">
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-2">Payment History</div>
                    @foreach ($invoice->payments->sortByDesc('paid_at') as $payment)
                        <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                            <span>{{ $payment->paid_at->format('M j, Y g:i A') }} · {{ $payment->method }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</span>
                            <strong>₱{{ number_format($payment->amount, 2) }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($invoice->emailDeliveries->isNotEmpty())
                <div class="mt-6 print:hidden">
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-2">Email Delivery History</div>
                    @foreach ($invoice->emailDeliveries->sortByDesc('created_at') as $delivery)
                        <div class="flex justify-between border-b border-slate-100 py-2 text-xs">
                            <span>{{ ucfirst($delivery->document_type) }} to {{ $delivery->recipient }}</span>
                            <span class="font-semibold {{ $delivery->status === 'sent' ? 'text-emerald-600' : ($delivery->status === 'failed' ? 'text-red-600' : 'text-amber-600') }}">{{ ucfirst($delivery->status) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Footer --}}
            <div class="mt-8 text-center text-xs text-slate-400">
                <p>Thank you for choosing {{ $clinic->clinic_name ?: 'DentalCare' }}!</p>
                @if ($clinic->phone || $clinic->email)
                    @php
                        $contact = collect([
                            $clinic->phone ? 'call '.$clinic->phone : null,
                            $clinic->email ? 'email us at '.$clinic->email : null,
                        ])->filter()->implode(' or ');
                    @endphp
                    <p class="mt-1">For questions, {{ $contact }}</p>
                @endif
                @if ($clinic->website)
                    <p class="mt-1">{{ $clinic->website }}</p>
                @endif
            </div>

        </div>
    </div>

    @unless($embedded)
    <div class="mt-4 flex gap-3 print:hidden">
        <a href="{{ route('billing.index') }}" class="flex-1 py-3 text-center rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">← Back to Billing</a>
        <a href="{{ route('billing.create') }}" class="flex-1 py-3 text-center rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition">+ New Invoice</a>
    </div>
    @endunless
</div>

@if (! $isPaid && ! $embedded)
<x-modal name="payment-record" title="Record Payment" max-width="lg">
    <form action="{{ route('billing.payments.store', $invoice) }}" method="POST" class="space-y-4">
        @csrf
        <div class="rounded-xl bg-slate-50 p-4 text-sm">Remaining balance: <strong>₱{{ number_format($invoice->balance, 2) }}</strong></div>
        <div><label class="mb-1 block text-sm font-medium">Amount</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->balance }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5"></div>
        <div><label class="mb-1 block text-sm font-medium">Method</label><select name="method" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5">@foreach(['Cash','GCash','Maya','Credit/Debit Card','PhilHealth','Bank Transfer','Other'] as $method)<option>{{ $method }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-sm font-medium">Reference</label><input type="text" name="reference" class="w-full rounded-xl border border-slate-200 px-4 py-2.5"></div>
        <div><label class="mb-1 block text-sm font-medium">Payment date and time</label><input type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5"></div>
        <div><label class="mb-1 block text-sm font-medium">Notes</label><textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-200 px-4 py-2.5"></textarea></div>
        <div class="flex justify-end gap-3 border-t pt-4"><button type="button" x-on:click="open=false" class="rounded-xl border px-4 py-2">Cancel</button><button type="submit" class="rounded-xl bg-emerald-500 px-4 py-2 font-semibold text-white">Record Payment</button></div>
    </form>
</x-modal>
@endif
@endsection
