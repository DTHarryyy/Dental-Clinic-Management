@extends('layouts.app')
@section('page_title', 'Patient Profile')

@section('content')

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('patients.index') }}" class="hover:text-emerald-600 transition">Patients</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">{{ $patient->name }}</span>
</div>


{{-- Header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="h-16 w-16 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl">
            {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $patient->name }}</h1>
            <div class="flex items-center gap-3 mt-1">
                <span class="text-sm text-slate-500">Patient #{{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}</span>
                <span class="text-slate-300">·</span>
                <span class="text-sm text-slate-500">{{ $patient->age ?? '—' }} {{ $patient->age ? 'yrs' : '' }} · {{ $patient->gender ?? '—' }}</span>
                @if ($patient->status === 'active')
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span> Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400 inline-block"></span> Inactive
                    </span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('appointments.create') }}" class="px-4 py-2.5 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-semibold text-sm transition"><i class="fa-solid fa-calendar-plus mr-1"></i> Book Appointment</a>
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'patient-edit-{{ $patient->id }}' } }))" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-pen mr-1"></i> Edit</button>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Left panel --}}
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100">Personal Details</h3>
            @php
                $details = [
                    ['label' => 'Date of Birth', 'value' => optional($patient->dob)->format('F j, Y') ?? '—'],
                    ['label' => 'Civil Status',  'value' => $patient->civil_status ?? '—'],
                    ['label' => 'Contact',       'value' => $patient->mobile ?? '—'],
                    ['label' => 'Email',         'value' => $patient->email ?? '—'],
                    ['label' => 'Address',       'value' => $patient->address ?? '—'],
                    ['label' => 'Emergency',     'value' => $patient->emergency_contact_name ? "{$patient->emergency_contact_name} — {$patient->emergency_contact_number}" : '—'],
                ];
            @endphp
            @foreach ($details as $d)
                <div>
                    <div class="text-xs text-slate-400 font-medium">{{ $d['label'] }}</div>
                    <div class="text-sm text-slate-700 mt-0.5">{{ $d['value'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100">Medical History</h3>
            <div>
                <div class="text-xs text-slate-400 font-medium">Allergies</div>
                <div class="text-sm text-slate-700 mt-0.5">{{ $patient->allergies ?: 'None' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-400 font-medium">Medications</div>
                <div class="text-sm text-slate-700 mt-0.5">{{ $patient->medications ?: 'None' }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-400 font-medium">Conditions</div>
                <div class="flex flex-wrap gap-1 mt-1">
                    @forelse (($patient->conditions ?? []) as $c)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-lg bg-red-50 text-red-600 border border-red-100">{{ $c }}</span>
                    @empty
                        <span class="text-sm text-slate-500">None reported</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-2">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100">Patient Since</h3>
            <div class="text-sm text-slate-600">{{ $patient->created_at->format('F j, Y') }}</div>
            <div class="text-xs text-slate-400">Last visit: {{ optional($patient->dentalRecords->first())->treatment_date?->format('M j, Y') ?? '—' }}</div>
        </div>
    </div>

    {{-- Right panel --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Dental Records --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-base text-slate-800">Treatment History</h2>
                <a href="{{ route('records.create') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition">+ Add Record</a>
            </div>
            <div class="space-y-3">
                @forelse ($patient->dentalRecords as $r)
                    <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                        <div class="h-10 w-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shrink-0"><i class="fa-solid fa-stethoscope"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-slate-800">{{ $r->procedure }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $r->treatment_date->format('M j, Y') }} · {{ $r->dentist->name ?? '—' }}</div>
                            <div class="text-xs text-slate-600 mt-1">{{ \Illuminate\Support\Str::limit($r->clinical_notes, 120) }}</div>
                        </div>
                        <a href="{{ route('records.show', $r) }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 shrink-0">View →</a>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No treatment records yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Billing --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-base text-slate-800">Billing History</h2>
                <a href="{{ route('billing.create') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-violet-500 text-white hover:bg-violet-600 transition">+ Invoice</a>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                        <th class="text-left pb-2 font-semibold">Date</th>
                        <th class="text-left pb-2 font-semibold">Invoice</th>
                        <th class="text-left pb-2 font-semibold">Amount</th>
                        <th class="text-left pb-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($patient->invoices as $b)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 text-slate-500">{{ $b->invoice_date->format('M j, Y') }}</td>
                            <td class="py-3 font-medium text-slate-700">{{ $b->invoice_number }}</td>
                            <td class="py-3 font-semibold text-slate-800">₱{{ number_format($b->total, 2) }}</td>
                            <td class="py-3">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-lg {{ $b->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($b->payment_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-slate-400">No invoices yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

{{-- Reuse the shared add/edit form dialog so the Edit button above works right here on the profile. --}}
<x-modal name="patient-edit-{{ $patient->id }}" title="Edit Patient" max-width="3xl" body-class="flex flex-col min-h-0">
    @include('patients._form-dialog', ['patient' => $patient])
</x-modal>
@endsection
