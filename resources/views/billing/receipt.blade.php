@extends('layouts.app')
@section('page_title', 'Receipt')

@section('content')
@php $bid = $id ?? 89; @endphp

<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="/billing" class="hover:text-emerald-600 transition">Billing</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">INV-{{ str_pad($bid, 4, '0', STR_PAD_LEFT) }}</span>
</div>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Receipt</h1>
    <div class="flex gap-2">
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-print mr-1"></i> Print</button>
        <button class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-envelope mr-1"></i> Email</button>
    </div>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" id="receipt">

        {{-- Header --}}
        <div class="bg-emerald-500 text-white px-8 py-8">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center"><i class="fa-solid fa-tooth text-xl"></i></div>
                        <div>
                            <div class="font-bold text-lg">DentalCare</div>
                            <div class="text-emerald-100 text-xs">Management System</div>
                        </div>
                    </div>
                    <div class="text-emerald-100 text-xs mt-2">123 Dental Ave, Quezon City</div>
                    <div class="text-emerald-100 text-xs">(02) 8123-4567</div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">RECEIPT</div>
                    <div class="text-emerald-200 text-sm mt-1">INV-{{ str_pad($bid, 4, '0', STR_PAD_LEFT) }}</div>
                    <div class="mt-3 inline-block bg-white text-emerald-700 text-xs font-bold px-3 py-1 rounded-lg">PAID</div>
                </div>
            </div>
        </div>

        <div class="px-8 py-6">

            {{-- Dates + Patient --}}
            <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-slate-100">
                <div>
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Billed To</div>
                    <div class="font-semibold text-slate-800">Maria Santos</div>
                    <div class="text-sm text-slate-500 mt-0.5">0917-111-1111</div>
                    <div class="text-xs text-slate-400 mt-0.5">Patient #0001</div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Invoice Date</div>
                    <div class="font-semibold text-slate-800">March 22, 2026</div>
                    <div class="text-xs text-slate-400 mt-3 font-semibold uppercase tracking-wide">Dentist</div>
                    <div class="text-sm text-slate-700">Dr. Reyes</div>
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
                    @php
                        $items = [
                            ['desc' => 'Teeth Cleaning (Prophylaxis)', 'qty' => 1, 'price' => 700],
                            ['desc' => 'Dental X-Ray',                  'qty' => 1, 'price' => 100],
                        ];
                    @endphp
                    @foreach ($items as $item)
                        <tr class="border-b border-slate-50">
                            <td class="py-3 text-slate-700">{{ $item['desc'] }}</td>
                            <td class="py-3 text-center text-slate-500">{{ $item['qty'] }}</td>
                            <td class="py-3 text-right text-slate-500">₱{{ number_format($item['price']) }}</td>
                            <td class="py-3 text-right font-medium text-slate-800">₱{{ number_format($item['qty'] * $item['price']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="space-y-2 text-sm border-t border-slate-100 pt-4">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>₱800</span></div>
                <div class="flex justify-between text-slate-500"><span>Discount</span><span>₱0</span></div>
                <div class="flex justify-between font-bold text-lg text-slate-800 pt-3 border-t border-slate-100">
                    <span>Total</span><span>₱800</span>
                </div>
            </div>

            {{-- Payment info --}}
            <div class="mt-6 bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center justify-between">
                <div>
                    <div class="text-xs text-emerald-600 font-semibold uppercase tracking-wide">Payment</div>
                    <div class="font-semibold text-emerald-800 mt-0.5">Cash — ₱800</div>
                    <div class="text-xs text-emerald-600">March 22, 2026</div>
                </div>
                <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center text-2xl text-emerald-600"><i class="fa-solid fa-circle-check"></i></div>
            </div>

            {{-- Footer --}}
            <div class="mt-8 text-center text-xs text-slate-400">
                <p>Thank you for choosing DentalCare!</p>
                <p class="mt-1">For questions, call (02) 8123-4567 or email us at info@dentalcare.com</p>
            </div>

        </div>
    </div>

    <div class="mt-4 flex gap-3">
        <a href="/billing" class="flex-1 py-3 text-center rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">← Back to Billing</a>
        <a href="/billing/create" class="flex-1 py-3 text-center rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition">+ New Invoice</a>
    </div>
</div>
@endsection
