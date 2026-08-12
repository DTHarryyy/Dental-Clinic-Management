@extends('layouts.app')
@section('page_title', 'Create Invoice')

@section('content')
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('billing.index') }}" class="hover:text-emerald-600 transition">Billing</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">New Invoice</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Create Invoice</h1>
    <a href="{{ route('billing.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="{{ route('billing.store') }}" method="POST" id="invoice-form">
    @csrf
    @if ($prefillRecord)
        <input type="hidden" name="dental_record_id" value="{{ $prefillRecord->id }}">
        <div class="mb-5 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800">
            Billing treatment #{{ $prefillRecord->id }}: {{ $prefillRecord->procedure }} on {{ $prefillRecord->treatment_date->format('M j, Y') }}.
        </div>
    @endif
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">

            {{-- Patient --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-4 pb-4 border-b border-slate-100">Bill To</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Patient <span class="text-red-500">*</span></label>
                        <x-patient-lookup :value="$prefillRecord?->patient_id" :label="$prefillRecord?->patient?->name" />
                        <p class="text-xs text-slate-400 mt-2">
                            No patient in the list? <a href="{{ route('patients.create') }}" class="text-emerald-600 font-medium hover:underline">Register a new patient →</a>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Invoice Date</label>
                        <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Due Date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-4 pb-4 border-b border-slate-100">Services / Items</h2>

                <div class="space-y-3" id="line-items"></div>

                <button type="button" id="add-item" class="mt-4 text-sm font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Add Line Item
                </button>

                {{-- Totals --}}
                <div class="mt-6 pt-5 border-t border-slate-100 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600"><span>Subtotal</span><span class="font-medium" id="subtotal-display">₱0.00</span></div>
                    <div class="flex justify-between text-slate-600">
                        <span>Discount</span>
                        <div class="flex items-center gap-2">
                            <input type="number" name="discount" id="discount-input" value="{{ old('discount', 0) }}" step="0.01" min="0" placeholder="0" class="w-20 px-2 py-1 rounded-lg border border-slate-200 text-xs text-right focus:outline-none focus:ring-1 focus:ring-emerald-200" />
                            <span class="text-slate-400">₱</span>
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-base text-slate-800 pt-2 border-t border-slate-100">
                        <span>Total</span><span id="total-display">₱0.00</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-4">Notes</h2>
                <textarea name="notes" rows="3" placeholder="Payment instructions or additional notes..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 resize-none">{{ old('notes') }}</textarea>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Initial Payment</h3>
                <input type="number" name="initial_payment_amount" step="0.01" min="0.01" placeholder="Leave blank if unpaid" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <input type="text" name="payment_reference" placeholder="Reference (optional)" class="mt-2 w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Payment Method</h3>
                <div class="space-y-2">
                    @foreach (['Cash', 'GCash', 'Maya', 'Credit/Debit Card', 'PhilHealth'] as $method)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="{{ $method }}" class="text-emerald-600 focus:ring-emerald-200" {{ $method === 'Cash' ? 'checked' : '' }} />
                            <span class="text-sm text-slate-700">{{ $method }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('billing.index') }}">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    Create Invoice
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const servicePrices = {!! $services->pluck('price', 'name')->toJson() !!};
    const serviceNames = Object.keys(servicePrices);
    const container = document.getElementById('line-items');
    const prefillItem = @json($prefillRecord ? ['description' => $prefillRecord->procedure, 'price' => (float) $prefillRecord->treatment_fee] : null);
    let rowCount = 0;

    function currency(n) {
        return '₱' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function addRow(description = '', qty = 1, price = 0) {
        const idx = rowCount++;
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-3 items-end line-item-row';

        const options = serviceNames.map(name => `<option value="${name}" data-price="${servicePrices[name]}">${name}</option>`).join('');

        row.innerHTML = `
            <div class="col-span-6">
                <label class="block text-xs font-medium text-slate-600 mb-1">Service / Description</label>
                <input type="text" list="service-list-${idx}" name="items[${idx}][description]" value="${description}" class="item-desc w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" required />
                <datalist id="service-list-${idx}">${options}</datalist>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Qty</label>
                <input type="number" name="items[${idx}][qty]" value="${qty}" min="1" class="item-qty w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" required />
            </div>
            <div class="col-span-3">
                <label class="block text-xs font-medium text-slate-600 mb-1">Price (₱)</label>
                <input type="number" name="items[${idx}][price]" value="${price}" step="0.01" min="0" class="item-price w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" required />
            </div>
            <div class="col-span-1 flex justify-center">
                <button type="button" class="remove-row h-9 w-9 rounded-xl bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition"><i class="fa-solid fa-xmark"></i></button>
            </div>
        `;

        container.appendChild(row);

        const descInput = row.querySelector('.item-desc');
        const priceInput = row.querySelector('.item-price');
        descInput.addEventListener('input', () => {
            if (servicePrices[descInput.value] !== undefined && !priceInput.dataset.touched) {
                priceInput.value = servicePrices[descInput.value];
            }
            recalculate();
        });
        priceInput.addEventListener('input', () => { priceInput.dataset.touched = '1'; recalculate(); });
        row.querySelector('.item-qty').addEventListener('input', recalculate);
        row.querySelector('.remove-row').addEventListener('click', () => { row.remove(); recalculate(); });
    }

    function recalculate() {
        let subtotal = 0;
        container.querySelectorAll('.line-item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            subtotal += qty * price;
        });
        const discount = parseFloat(document.getElementById('discount-input').value) || 0;
        const total = Math.max(subtotal - discount, 0);
        document.getElementById('subtotal-display').textContent = currency(subtotal);
        document.getElementById('total-display').textContent = currency(total);
    }

    document.getElementById('add-item').addEventListener('click', () => addRow());
    document.getElementById('discount-input').addEventListener('input', recalculate);

    if (prefillItem) {
        addRow(prefillItem.description, 1, prefillItem.price);
    } else {
        addRow();
        addRow();
    }
    recalculate();

    document.getElementById('invoice-form').addEventListener('submit', function (e) {
        if (container.querySelectorAll('.line-item-row').length === 0) {
            e.preventDefault();
            alert('Add at least one line item.');
        }
    });
</script>
@endpush
@endsection
