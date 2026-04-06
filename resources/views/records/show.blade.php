@extends('layouts.app')
@section('page_title', 'Treatment Record')

@section('content')
@php $rid = $id ?? 1; @endphp

<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="/records" class="hover:text-emerald-600 transition">Dental Records</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Record #{{ str_pad($rid, 4, '0', STR_PAD_LEFT) }}</span>
</div>

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Treatment Record</h1>
        <p class="text-slate-500 text-sm mt-0.5">Mar 22, 2026 · Maria Santos</p>
    </div>
    <div class="flex gap-2">
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-print mr-1"></i> Print</button>
        <a href="/records/create" class="px-4 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-600 text-white font-semibold text-sm transition">+ New Record</a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Left --}}
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100 mb-3">Patient</h3>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">MS</div>
                <div>
                    <a href="/patients/1" class="font-semibold text-slate-800 hover:text-emerald-600 transition">Maria Santos</a>
                    <div class="text-xs text-slate-500">Patient #0001</div>
                </div>
            </div>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Age</span><span class="text-slate-700">34 yrs · Female</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Contact</span><span class="text-slate-700">0917-111-1111</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Allergy</span><span class="text-red-600 font-medium">Penicillin</span></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100 mb-3">Record Info</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Record ID</span><span class="text-slate-700 font-mono">#{{ str_pad($rid, 4, '0', STR_PAD_LEFT) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Date</span><span class="text-slate-700">Mar 22, 2026</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Dentist</span><span class="text-slate-700">Dr. Reyes</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Next Visit</span><span class="text-slate-700">Jun 22, 2026</span></div>
            </div>
        </div>
    </div>

    {{-- Right --}}
    <div class="xl:col-span-2 space-y-5">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Treatment Summary</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Procedure</div>
                    <div class="flex items-center gap-2 font-semibold text-slate-800"><i class="fa-solid fa-tooth text-teal-500"></i> Teeth Cleaning</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Area Treated</div>
                    <div class="font-medium text-slate-700">Full Mouth</div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Clinical Notes</div>
                    <div class="bg-slate-50 rounded-xl border border-slate-100 p-4 text-sm text-slate-700 leading-relaxed">
                        Prophylaxis performed on all quadrants. Supragingival and subgingival scaling completed. No active caries detected. Patient was advised to use fluoride toothpaste and floss daily. Mild gingival inflammation noted on lower anteriors — instructed on proper brushing technique.
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Prescription / Medications</div>
                    <div class="bg-amber-50 rounded-xl border border-amber-100 p-4 text-sm text-slate-700">
                        <i class="fa-solid fa-pills text-amber-600"></i> No medications prescribed. Advised Listerine mouthwash twice daily for 2 weeks.
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-base text-slate-800">Billing</h2>
                <a href="/billing/1/receipt" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-violet-50 hover:bg-violet-100 text-violet-700 transition">View Receipt</a>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div>
                    <div class="font-semibold text-sm text-slate-800">Teeth Cleaning</div>
                    <div class="text-xs text-slate-500">Invoice #INV-0089</div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-slate-800">₱800</div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700">Paid</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
