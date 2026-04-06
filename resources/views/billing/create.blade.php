@extends('layouts.app')
@section('page_title', 'Create Invoice')

@section('content')
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="/billing" class="hover:text-emerald-600 transition">Billing</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">New Invoice</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Create Invoice</h1>
    <a href="/billing" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="#" method="POST">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">

            {{-- Patient --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-4 pb-4 border-b border-slate-100">Bill To</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Patient <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">— Select patient —</option>
                            <option>Maria Santos</option>
                            <option>Jose Dela Cruz</option>
                            <option>Ana Reyes</option>
                            <option>Luis Gomez</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Invoice Date</label>
                        <input type="date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Due Date</label>
                        <input type="date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-4 pb-4 border-b border-slate-100">Services / Items</h2>

                <div class="space-y-3" id="line-items">
                    @foreach ([
                        ['service' => 'Teeth Cleaning', 'qty' => 1, 'price' => 800],
                        ['service' => 'Dental X-Ray',   'qty' => 1, 'price' => 350],
                    ] as $item)
                        <div class="grid grid-cols-12 gap-3 items-end">
                            <div class="col-span-6">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Service / Description</label>
                                <input type="text" value="{{ $item['service'] }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Qty</label>
                                <input type="number" value="{{ $item['qty'] }}" min="1" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                            </div>
                            <div class="col-span-3">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Price (₱)</label>
                                <input type="number" value="{{ $item['price'] }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                            </div>
                            <div class="col-span-1 flex justify-center">
                                <button type="button" class="h-9 w-9 rounded-xl bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="mt-4 text-sm font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Add Line Item
                </button>

                {{-- Totals --}}
                <div class="mt-6 pt-5 border-t border-slate-100 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600"><span>Subtotal</span><span class="font-medium">₱1,150</span></div>
                    <div class="flex justify-between text-slate-600">
                        <span>Discount</span>
                        <div class="flex items-center gap-2">
                            <input type="number" value="0" placeholder="0" class="w-20 px-2 py-1 rounded-lg border border-slate-200 text-xs text-right focus:outline-none focus:ring-1 focus:ring-emerald-200" />
                            <span class="text-slate-400">₱</span>
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-base text-slate-800 pt-2 border-t border-slate-100">
                        <span>Total</span><span>₱1,150</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-4">Notes</h2>
                <textarea rows="3" placeholder="Payment instructions or additional notes..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 resize-none"></textarea>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Payment Status</h3>
                <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="unpaid" selected>Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                </select>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Payment Method</h3>
                <div class="space-y-2">
                    @foreach (['Cash', 'GCash', 'Maya', 'Credit/Debit Card', 'PhilHealth'] as $method)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" class="text-emerald-600 focus:ring-emerald-200" {{ $method === 'Cash' ? 'checked' : '' }} />
                            <span class="text-sm text-slate-700">{{ $method }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a href="/billing">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    Create Invoice
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
