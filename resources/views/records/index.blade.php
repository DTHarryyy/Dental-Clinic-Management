@extends('layouts.app')
@section('page_title', 'Dental Records')

@section('content')
@php
    $records = [
        ['id' => 1,  'patient' => 'Maria Santos',     'service' => 'Teeth Cleaning',   'dentist' => 'Dr. Reyes', 'date' => 'Mar 22, 2026', 'notes' => 'Prophylaxis done. No cavities found.'],
        ['id' => 2,  'patient' => 'Jose Dela Cruz',   'service' => 'Root Canal',        'dentist' => 'Dr. Reyes', 'date' => 'Mar 21, 2026', 'notes' => 'RCT performed on lower molar #46. 3 sessions.'],
        ['id' => 3,  'patient' => 'Ana Reyes',        'service' => 'Dental Filling',    'dentist' => 'Dr. Reyes', 'date' => 'Mar 18, 2026', 'notes' => 'Composite resin on upper premolar.'],
        ['id' => 4,  'patient' => 'Luis Gomez',       'service' => 'Tooth Extraction',  'dentist' => 'Dr. Reyes', 'date' => 'Mar 15, 2026', 'notes' => 'Extracted wisdom tooth #38.'],
        ['id' => 5,  'patient' => 'Rosa Bautista',    'service' => 'Orthodontic Check', 'dentist' => 'Dr. Reyes', 'date' => 'Mar 10, 2026', 'notes' => 'Braces adjustment. Next: Apr 10.'],
        ['id' => 6,  'patient' => 'Carlos Mendoza',   'service' => 'Consultation',      'dentist' => 'Dr. Reyes', 'date' => 'Mar 5, 2026',  'notes' => 'Initial checkup. X-ray ordered.'],
        ['id' => 7,  'patient' => 'Elena Villanueva', 'service' => 'Teeth Whitening',   'dentist' => 'Dr. Reyes', 'date' => 'Feb 28, 2026', 'notes' => 'In-office bleaching 45 min.'],
    ];

    $serviceIcons = [
        'Teeth Cleaning'   => '<i class="fa-solid fa-tooth"></i>',
        'Root Canal'       => '<i class="fa-solid fa-tooth"></i>',
        'Dental Filling'   => '<i class="fa-solid fa-tooth"></i>',
        'Tooth Extraction' => '<i class="fa-solid fa-wrench"></i>',
        'Orthodontic Check'=> '<i class="fa-solid fa-teeth"></i>',
        'Consultation'     => '<i class="fa-solid fa-clipboard-list"></i>',
        'Teeth Whitening'  => '<i class="fa-solid fa-star"></i>',
    ];
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Dental Records</h1>
        <p class="text-slate-500 text-sm mt-0.5">Patient treatment history and clinical notes</p>
    </div>
    <a href="/records/create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-plus"></i> Add Record
    </a>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" placeholder="Search by patient or service..." class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <input type="date" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option>All Services</option>
        <option>Teeth Cleaning</option>
        <option>Root Canal</option>
        <option>Dental Filling</option>
        <option>Tooth Extraction</option>
        <option>Consultation</option>
    </select>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <th class="text-left px-5 py-3.5 font-semibold">Patient</th>
                <th class="text-left px-5 py-3.5 font-semibold">Service</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden md:table-cell">Dentist</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden lg:table-cell">Date</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden xl:table-cell">Notes</th>
                <th class="text-right px-5 py-3.5 font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($records as $r)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        <a href="/patients/{{ $r['id'] }}" class="font-semibold text-slate-800 hover:text-emerald-600 transition">{{ $r['patient'] }}</a>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-700">
                            <span>{!! $serviceIcons[$r['service']] ?? '<i class="fa-solid fa-stethoscope"></i>' !!}</span>
                            {{ $r['service'] }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden md:table-cell">{{ $r['dentist'] }}</td>
                    <td class="px-5 py-4 text-slate-500 hidden lg:table-cell">{{ $r['date'] }}</td>
                    <td class="px-5 py-4 text-slate-500 text-xs hidden xl:table-cell max-w-xs truncate">{{ $r['notes'] }}</td>
                    <td class="px-5 py-4 text-right">
                        <a href="/records/{{ $r['id'] }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 transition">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-500">
        <span>Showing 1–7 of 7 records</span>
        <div class="flex items-center gap-1">
            <button class="px-3 py-1.5 rounded-lg bg-emerald-500 text-white text-xs font-semibold">1</button>
        </div>
    </div>
</div>
@endsection
