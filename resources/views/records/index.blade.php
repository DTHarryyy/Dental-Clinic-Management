@extends('layouts.app')
@section('page_title', 'Dental Records')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Dental Records</h1>
        <p class="text-slate-500 text-sm mt-0.5">Patient treatment history and clinical notes</p>
    </div>
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'record-create' } }))" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-plus"></i> Add Record
    </button>
</div>

{{-- Filters --}}
<form method="GET" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by patient or service..." class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold">Search</button>
</form>

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
            @forelse ($records as $r)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        <a href="{{ route('patients.show', $r->patient) }}" class="font-semibold text-slate-800 hover:text-emerald-600 transition">{{ $r->patient->name }}</a>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-700">
                            <i class="fa-solid fa-tooth text-teal-500"></i>
                            {{ $r->procedure }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden md:table-cell">{{ $r->dentist->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-500 hidden lg:table-cell">{{ $r->treatment_date->format('M j, Y') }}</td>
                    <td class="px-5 py-4 text-slate-500 text-xs hidden xl:table-cell max-w-xs truncate">{{ $r->clinical_notes }}</td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('records.show', $r) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 transition">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-400">No treatment records yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($records->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $records->links() }}
        </div>
    @endif
</div>

<x-modal name="record-create" title="Add Treatment Record" max-width="3xl">
    @include('records._form-dialog')
</x-modal>

<x-modal name="patient-create" title="Add New Patient" max-width="4xl">
    @include('patients._form-dialog', ['patient' => null])
</x-modal>
@endsection
