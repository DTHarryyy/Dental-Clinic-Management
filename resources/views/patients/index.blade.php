@extends('layouts.app')
@section('page_title', 'Patients')

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Patients</h1>
        <p class="text-slate-500 text-sm mt-0.5">{{ $patients->total() }} registered patients</p>
    </div>
    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'patient-create' } }))" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-user-plus"></i> Add Patient
    </button>
</div>

{{-- Filters --}}
<form method="GET" data-auto-filter="patients" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search patients..." autocomplete="off" class="w-full pl-8 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <select name="status" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option {{ !request('status') ? 'selected' : '' }}>All Status</option>
        <option {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
        <option {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    <select name="gender" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <option {{ !request('gender') ? 'selected' : '' }}>All Gender</option>
        <option {{ request('gender') === 'Male' ? 'selected' : '' }}>Male</option>
        <option {{ request('gender') === 'Female' ? 'selected' : '' }}>Female</option>
    </select>
</form>

{{-- Table --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                <th class="text-left px-5 py-3.5 font-semibold">Patient</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden sm:table-cell">Contact</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden md:table-cell">Age / Gender</th>
                <th class="text-left px-5 py-3.5 font-semibold hidden lg:table-cell">Last Visit</th>
                <th class="text-left px-5 py-3.5 font-semibold">Status</th>
                <th class="text-right px-5 py-3.5 font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($patients as $p)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper(substr($p->first_name, 0, 1) . substr($p->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800">{{ $p->name }}</div>
                                <div class="text-xs text-slate-400">ID #{{ str_pad($p->id, 4, '0', STR_PAD_LEFT) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-slate-600 hidden sm:table-cell">{{ $p->mobile ?? '—' }}</td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <span class="text-slate-600">{{ $p->age ?? '—' }} {{ $p->age ? 'yrs' : '' }}</span>
                        <span class="text-slate-400 ml-1">· {{ $p->gender ?? '—' }}</span>
                    </td>
                    <td class="px-5 py-4 text-slate-500 hidden lg:table-cell">{{ $p->last_visit ? \Illuminate\Support\Carbon::parse($p->last_visit)->format('M j, Y') : '—' }}</td>
                    <td class="px-5 py-4">
                        @if($p->status === 'active')
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400 inline-block"></span> Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" data-patient-detail="{{ route('patients.detail-frame', $p) }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">View</button>
                            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'patient-edit-{{ $p->id }}' } }))" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Edit</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-400">No patients found. <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'patient-create' } }))" class="text-emerald-600 font-semibold hover:underline">Add the first one</button>.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if ($patients->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $patients->links() }}
        </div>
    @endif
</div>

<x-modal name="patient-create" title="Add New Patient" max-width="3xl" body-class="flex flex-col min-h-0">
    @include('patients._form-dialog', ['patient' => null])
</x-modal>

<x-modal name="patient-view" title="Patient Details" max-width="4xl" body-class="flex flex-col min-h-0">
    <turbo-frame id="patient-detail-frame" class="min-h-64" loading="lazy">
        <div class="flex min-h-64 items-center justify-center text-sm text-slate-400" aria-busy="true">Loading patient history…</div>
    </turbo-frame>
</x-modal>

@foreach ($patients as $p)
    <x-modal name="patient-edit-{{ $p->id }}" title="Edit Patient" max-width="3xl" body-class="flex flex-col min-h-0">
        @include('patients._form-dialog', ['patient' => $p])
    </x-modal>
@endforeach

@if ($viewPatientId)
    <script>
        document.addEventListener('turbo:load', () => window.openPatientDetail(@js(route('patients.detail-frame', $viewPatientId))), { once: true });
    </script>
@endif
@endsection
