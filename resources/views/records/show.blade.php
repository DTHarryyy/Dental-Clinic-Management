@extends('layouts.app')
@section('page_title', 'Treatment Record')

@section('content')

<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('records.index') }}" class="hover:text-emerald-600 transition">Dental Records</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Record #{{ str_pad($record->id, 4, '0', STR_PAD_LEFT) }}</span>
</div>

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Treatment Record</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ $record->treatment_date->format('M j, Y') }} · {{ $record->patient->name }}</p>
    </div>
    <div class="flex gap-2">
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-print mr-1"></i> Print</button>
        <a href="{{ route('records.create') }}" class="px-4 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-600 text-white font-semibold text-sm transition">+ New Record</a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Left --}}
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100 mb-3">Patient</h3>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr($record->patient->first_name, 0, 1) . substr($record->patient->last_name, 0, 1)) }}
                </div>
                <div>
                    <a href="{{ route('patients.show', $record->patient) }}" class="font-semibold text-slate-800 hover:text-emerald-600 transition">{{ $record->patient->name }}</a>
                    <div class="text-xs text-slate-500">Patient #{{ str_pad($record->patient->id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Age</span><span class="text-slate-700">{{ $record->patient->age ?? '—' }} yrs · {{ $record->patient->gender ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Contact</span><span class="text-slate-700">{{ $record->patient->mobile ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Allergy</span><span class="text-red-600 font-medium">{{ $record->patient->allergies ?: 'None' }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100 mb-3">Record Info</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Record ID</span><span class="text-slate-700 font-mono">#{{ str_pad($record->id, 4, '0', STR_PAD_LEFT) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Date</span><span class="text-slate-700">{{ $record->treatment_date->format('M j, Y') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Dentist</span><span class="text-slate-700">{{ $record->dentist->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Next Visit</span><span class="text-slate-700">{{ optional($record->next_appointment_date)->format('M j, Y') ?? '—' }}</span></div>
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
                    <div class="flex items-center gap-2 font-semibold text-slate-800"><i class="fa-solid fa-tooth text-teal-500"></i> {{ $record->procedure }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Area Treated</div>
                    <div class="font-medium text-slate-700">{{ $record->tooth_area ?: 'Full Mouth' }}</div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Clinical Notes</div>
                    <div class="bg-slate-50 rounded-xl border border-slate-100 p-4 text-sm text-slate-700 leading-relaxed">
                        {{ $record->clinical_notes }}
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Prescription / Medications</div>
                    <div class="bg-amber-50 rounded-xl border border-amber-100 p-4 text-sm text-slate-700">
                        <i class="fa-solid fa-pills text-amber-600"></i> {{ $record->prescription ?: 'No medications prescribed.' }}
                    </div>
                </div>
            </div>
        </div>

        @can('viewBilling', $record->patient)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-base text-slate-800">Billing</h2>
                @if ($record->invoice)
                    <a href="{{ route('billing.receipt', $record->invoice) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-violet-50 hover:bg-violet-100 text-violet-700 transition">View Receipt</a>
                @endif
            </div>
            @if ($record->invoice)
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <div>
                        <div class="font-semibold text-sm text-slate-800">{{ $record->procedure }}</div>
                        <div class="text-xs text-slate-500">Invoice #{{ $record->invoice->invoice_number }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-slate-800">₱{{ number_format($record->invoice->total, 2) }}</div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-lg {{ $record->invoice->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($record->invoice->payment_status) }}</span>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-400">No invoice linked to this record. <a href="{{ route('billing.create', ['record' => $record]) }}" class="text-emerald-600 font-semibold">Create one →</a></p>
            @endif
        </div>
        @endcan

        @can('publish', $record)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-base text-slate-800">Patient-Facing Summary</h2>
                    <p class="mt-1 text-xs text-slate-500">Only these published fields appear in the patient portal.</p>
                </div>
                @if($record->published_at)
                    <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Published {{ $record->published_at->format('M j, Y') }}</span>
                @else
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Unpublished</span>
                @endif
            </div>
            <form action="{{ route('records.publish', $record) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Summary <span class="text-red-500">*</span></label>
                    <textarea name="patient_summary" rows="5" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">{{ old('patient_summary', $record->patient_summary) }}</textarea>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Aftercare instructions</label>
                    <textarea name="aftercare_instructions" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">{{ old('aftercare_instructions', $record->aftercare_instructions) }}</textarea>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                    @if($record->published_at)
                        <button type="submit" form="unpublish-summary-{{ $record->id }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-red-50 px-4 text-sm font-semibold text-red-600 hover:bg-red-100">Unpublish</button>
                    @endif
                    <button class="primary-action"><i class="fa-solid fa-eye"></i> {{ $record->published_at ? 'Update published summary' : 'Publish summary' }}</button>
                </div>
            </form>
            @if($record->published_at)
                <form id="unpublish-summary-{{ $record->id }}" action="{{ route('records.unpublish', $record) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
            @endif
        </div>
        @endcan

    </div>
</div>
@endsection
