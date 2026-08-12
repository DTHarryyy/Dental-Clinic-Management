@php
    $isPaid = $invoice->payment_status === 'paid';
    $statusColors = ['Paid' => 'bg-emerald-100 text-emerald-700', 'Partial' => 'bg-blue-100 text-blue-700', 'Unpaid' => 'bg-amber-100 text-amber-700', 'Overdue' => 'bg-red-100 text-red-700'];
@endphp

<div class="border-b border-slate-100 bg-slate-50 px-6 py-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="font-mono text-lg font-bold text-slate-800">{{ $invoice->invoice_number }}</h3>
                <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $statusColors[$invoice->display_status] ?? 'bg-slate-100 text-slate-600' }}">{{ $invoice->display_status }}</span>
            </div>
            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $invoice->patient?->name ?? 'Unknown patient' }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ $invoice->invoice_date->format('M j, Y') }}@if($invoice->due_date) · Due {{ $invoice->due_date->format('M j, Y') }}@endif @if($invoice->dentalRecord?->dentist) · {{ $invoice->dentalRecord->dentist->name }}@endif</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('billing.send', $invoice) }}" method="POST" data-ajax-form data-loading-text="Sending...">
                @csrf
                <button type="submit" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700"><i class="fa-solid fa-envelope mr-1"></i>Email {{ $isPaid ? 'Receipt' : 'Invoice' }}</button>
            </form>
            <button type="button" data-receipt-preview-url="{{ route('billing.receipt', ['invoice' => $invoice, 'embedded' => 1]) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-300"><i class="fa-solid fa-print mr-1" aria-hidden="true"></i>View &amp; Print</button>
        </div>
    </div>
    @if(!$invoice->patient?->email)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"><i class="fa-solid fa-circle-info mr-1"></i>This patient has no valid email. Payments can still be recorded, but billing documents cannot be emailed.</div>
    @endif
</div>

<div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-5">
    <div class="space-y-6 lg:col-span-3">
        <section>
            <h4 class="mb-3 text-sm font-bold text-slate-800">Invoice items</h4>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="w-full text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-4 py-2.5 text-left">Description</th><th class="px-3 py-2.5 text-center">Qty</th><th class="px-4 py-2.5 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@foreach($invoice->items as $item)<tr><td class="px-4 py-3 text-slate-700">{{ $item->description }}</td><td class="px-3 py-3 text-center text-slate-500">{{ $item->qty }}</td><td class="px-4 py-3 text-right font-medium">₱{{ number_format($item->qty * $item->price, 2) }}</td></tr>@endforeach</tbody></table>
            </div>
            <div class="mt-3 w-full space-y-1.5 text-sm">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>₱{{ number_format($invoice->subtotal, 2) }}</span></div>
                @if($invoice->discount > 0)<div class="flex justify-between text-slate-500"><span>Discount</span><span>−₱{{ number_format($invoice->discount, 2) }}</span></div>@endif
                <div class="flex justify-between border-t pt-2 font-bold text-slate-800"><span>Total</span><span>₱{{ number_format($invoice->total, 2) }}</span></div>
                <div class="flex justify-between text-emerald-600"><span>Paid</span><span>₱{{ number_format($invoice->amount_paid, 2) }}</span></div>
                <div class="flex justify-between text-base font-bold text-slate-900"><span>Balance</span><span>₱{{ number_format($invoice->balance, 2) }}</span></div>
            </div>
        </section>

        <section>
            <h4 class="mb-3 text-sm font-bold text-slate-800">Payment history</h4>
            @forelse($invoice->payments as $payment)
                <div class="mb-2 rounded-xl border border-slate-200 px-4 py-3 text-sm">
                    <div class="flex justify-between gap-3"><span class="font-semibold text-slate-800">{{ $payment->method }}</span><span class="font-bold text-emerald-700">₱{{ number_format($payment->amount, 2) }}</span></div>
                    <div class="mt-1 text-xs text-slate-500">{{ $payment->paid_at->format('M j, Y · g:i A') }}@if($payment->reference) · Ref: {{ $payment->reference }}@endif @if($payment->receiver) · Received by {{ $payment->receiver->name }}@endif</div>
                    @if($payment->notes)<p class="mt-2 text-xs text-slate-600">{{ $payment->notes }}</p>@endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 px-4 py-5 text-center text-sm text-slate-400">No payments recorded yet.</div>
            @endforelse
        </section>
    </div>

    <aside class="lg:col-span-2">
        @if($isPaid)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center"><i class="fa-solid fa-circle-check text-3xl text-emerald-500"></i><h4 class="mt-3 font-bold text-emerald-800">Paid in full</h4><p class="mt-1 text-xs text-emerald-700">The payment ledger covers the complete invoice total.</p></div>
        @else
            <form action="{{ route('billing.payments.store', $invoice) }}" method="POST" data-ajax-form data-loading-text="Recording..." class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                @csrf
                <h4 class="font-bold text-slate-800">Record payment</h4>
                <p class="mt-1 text-xs text-slate-500">Status updates automatically from the amount received.</p>
                <div class="mt-4">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label for="invoice-payment-amount" class="text-xs font-semibold text-slate-600">Amount</label>
                        <button type="button" data-use-full-balance="{{ number_format($invoice->balance, 2, '.', '') }}" data-default-label="Pay remaining ₱{{ number_format($invoice->balance, 2) }}" onclick="this.form.elements.amount.value = this.dataset.useFullBalance" aria-pressed="false" aria-label="Fill amount with the remaining balance of ₱{{ number_format($invoice->balance, 2) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                            <i class="fa-solid fa-coins" aria-hidden="true"></i>
                            <span data-full-balance-label>Pay remaining ₱{{ number_format($invoice->balance, 2) }}</span>
                        </button>
                    </div>
                    <div class="relative"><span class="absolute left-3 top-2.5 text-sm text-slate-400">₱</span><input id="invoice-payment-amount" type="number" name="amount" step="0.01" min="0.01" max="{{ number_format($invoice->balance, 2, '.', '') }}" inputmode="decimal" required class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-7 pr-3 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"></div>
                    <p data-full-balance-feedback role="status" aria-live="polite" class="mt-1.5 hidden text-xs font-medium text-emerald-700"></p>
                </div>
                <div class="mt-3"><label class="mb-1.5 block text-xs font-semibold text-slate-600">Payment method</label><select name="method" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm"><option value="">Select method</option>@foreach($paymentMethods as $method)<option value="{{ $method }}">{{ $method }}</option>@endforeach</select></div>
                <div class="mt-3"><label class="mb-1.5 block text-xs font-semibold text-slate-600">Reference <span class="font-normal text-slate-400">(optional)</span></label><input type="text" name="reference" maxlength="255" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm"></div>
                <div class="mt-3"><label class="mb-1.5 block text-xs font-semibold text-slate-600">Payment date and time</label><input type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm"></div>
                <div class="mt-3"><label class="mb-1.5 block text-xs font-semibold text-slate-600">Notes <span class="font-normal text-slate-400">(optional)</span></label><textarea name="notes" rows="2" maxlength="2000" class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm"></textarea></div>
                <button type="submit" class="mt-4 w-full rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-600"><i class="fa-solid fa-money-bill-wave mr-1.5"></i>Record Payment</button>
            </form>
        @endif
    </aside>
</div>
