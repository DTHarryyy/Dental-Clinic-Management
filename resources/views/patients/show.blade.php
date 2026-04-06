@extends('layouts.app')
@section('page_title', 'Patient Profile')

@section('content')
@php
    $patient = [
        'id'       => $id ?? 1,
        'name'     => 'Maria Santos',
        'age'      => 34,
        'gender'   => 'Female',
        'dob'      => 'March 5, 1992',
        'civil'    => 'Single',
        'contact'  => '0917-111-1111',
        'email'    => 'maria.santos@email.com',
        'address'  => 'Blk 3 Lot 5, Sampaguita St., Brgy. 15, Quezon City',
        'emergency' => 'Sofia Santos — 0917-999-8888',
        'allergy'  => 'Penicillin',
        'meds'     => 'None',
        'conditions' => ['Hypertension'],
        'status'   => 'Active',
        'since'    => 'Jan 10, 2024',
        'lastVisit' => 'Mar 22, 2026',
    ];

    $records = [
        ['date' => 'Mar 22, 2026', 'service' => 'Teeth Cleaning',   'dentist' => 'Dr. Reyes', 'notes' => 'Prophylaxis done. No cavities.'],
        ['date' => 'Jan 15, 2026', 'service' => 'Dental Filling',   'dentist' => 'Dr. Reyes', 'notes' => 'Composite resin filling on upper molar.'],
        ['date' => 'Oct 08, 2025', 'service' => 'Consultation',     'dentist' => 'Dr. Reyes', 'notes' => 'Initial checkup. X-ray taken.'],
    ];

    $billings = [
        ['date' => 'Mar 22, 2026', 'desc' => 'Teeth Cleaning', 'amount' => '₱800',   'status' => 'Paid'],
        ['date' => 'Jan 15, 2026', 'desc' => 'Dental Filling', 'amount' => '₱1,500', 'status' => 'Paid'],
        ['date' => 'Oct 08, 2025', 'desc' => 'Consultation',   'amount' => '₱300',   'status' => 'Paid'],
    ];
@endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="/patients" class="hover:text-emerald-600 transition">Patients</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">{{ $patient['name'] }}</span>
</div>

{{-- Header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="h-16 w-16 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl">MS</div>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $patient['name'] }}</h1>
            <div class="flex items-center gap-3 mt-1">
                <span class="text-sm text-slate-500">Patient #{{ str_pad($patient['id'], 4, '0', STR_PAD_LEFT) }}</span>
                <span class="text-slate-300">·</span>
                <span class="text-sm text-slate-500">{{ $patient['age'] }} yrs · {{ $patient['gender'] }}</span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span> Active
                </span>
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="/appointments/create" class="px-4 py-2.5 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-semibold text-sm transition"><i class="fa-solid fa-calendar-plus mr-1"></i> Book Appointment</a>
        <a href="/patients/{{ $patient['id'] }}/edit" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-pen mr-1"></i> Edit</a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Left panel --}}
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100">Personal Details</h3>
            @php
                $details = [
                    ['label' => 'Date of Birth', 'value' => $patient['dob']],
                    ['label' => 'Civil Status',  'value' => $patient['civil']],
                    ['label' => 'Contact',       'value' => $patient['contact']],
                    ['label' => 'Email',         'value' => $patient['email']],
                    ['label' => 'Address',       'value' => $patient['address']],
                    ['label' => 'Emergency',     'value' => $patient['emergency']],
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
                <div class="text-sm text-slate-700 mt-0.5">{{ $patient['allergy'] }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-400 font-medium">Medications</div>
                <div class="text-sm text-slate-700 mt-0.5">{{ $patient['meds'] }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-400 font-medium">Conditions</div>
                <div class="flex flex-wrap gap-1 mt-1">
                    @foreach ($patient['conditions'] as $c)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-lg bg-red-50 text-red-600 border border-red-100">{{ $c }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-2">
            <h3 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100">Patient Since</h3>
            <div class="text-sm text-slate-600">{{ $patient['since'] }}</div>
            <div class="text-xs text-slate-400">Last visit: {{ $patient['lastVisit'] }}</div>
        </div>
    </div>

    {{-- Right panel --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Dental Records --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-base text-slate-800">Treatment History</h2>
                <a href="/records/create" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition">+ Add Record</a>
            </div>
            <div class="space-y-3">
                @foreach ($records as $r)
                    <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                        <div class="h-10 w-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shrink-0"><i class="fa-solid fa-stethoscope"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-slate-800">{{ $r['service'] }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $r['date'] }} · {{ $r['dentist'] }}</div>
                            <div class="text-xs text-slate-600 mt-1">{{ $r['notes'] }}</div>
                        </div>
                        <a href="/records/1" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 shrink-0">View →</a>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Billing --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-base text-slate-800">Billing History</h2>
                <a href="/billing/create" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-violet-500 text-white hover:bg-violet-600 transition">+ Invoice</a>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                        <th class="text-left pb-2 font-semibold">Date</th>
                        <th class="text-left pb-2 font-semibold">Description</th>
                        <th class="text-left pb-2 font-semibold">Amount</th>
                        <th class="text-left pb-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($billings as $b)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 text-slate-500">{{ $b['date'] }}</td>
                            <td class="py-3 font-medium text-slate-700">{{ $b['desc'] }}</td>
                            <td class="py-3 font-semibold text-slate-800">{{ $b['amount'] }}</td>
                            <td class="py-3">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-lg {{ $b['status'] === 'Paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $b['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
