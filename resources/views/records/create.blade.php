@extends('layouts.app')
@section('page_title', 'Add Treatment Record')

@section('content')
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('records.index') }}" class="hover:text-emerald-600 transition">Dental Records</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Add Record</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add Treatment Record</h1>
    <a href="{{ route('records.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="{{ route('records.store') }}" method="POST">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">

            {{-- Patient --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Patient</h2>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Patient <span class="text-red-500">*</span></label>
                    <x-patient-lookup />
                    <p class="text-xs text-slate-400 mt-2">
                        No patient in the list? <a href="{{ route('patients.create') }}" class="text-emerald-600 font-medium hover:underline">Register a new patient →</a>
                    </p>
                </div>
            </div>

            {{-- Treatment --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Treatment Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Treatment <span class="text-red-500">*</span></label>
                        <input type="date" name="treatment_date" value="{{ old('treatment_date', date('Y-m-d')) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Attending Dentist <span class="text-red-500">*</span></label>
                        <select name="dentist_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">— Select dentist —</option>
                            @foreach ($dentists as $d)
                                <option value="{{ $d->id }}" {{ old('dentist_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Service / Procedure <span class="text-red-500">*</span></label>
                        <select name="procedure" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required>
                            <option value="">— Select procedure —</option>
                            @foreach ($services as $svc)
                                <option {{ old('procedure') === $svc ? 'selected' : '' }}>{{ $svc }}</option>
                            @endforeach
                        </select>
                        @if ($services->isEmpty())
                            <p class="text-xs text-amber-600 mt-1">No services configured yet. @can('settings.view')<a href="{{ route('settings.services') }}" class="font-semibold underline">Add them in Settings →</a>@else Contact an administrator.@endcan</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tooth / Area Treated</label>
                        <input type="text" name="tooth_area" value="{{ old('tooth_area') }}" placeholder="e.g. Upper molar #16" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Next Appointment</label>
                        <input type="date" name="next_appointment_date" value="{{ old('next_appointment_date') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Clinical Notes <span class="text-red-500">*</span></label>
                        <textarea name="clinical_notes" rows="5" placeholder="Describe the procedure, findings, medications given, and follow-up instructions..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none" required>{{ old('clinical_notes') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Prescription / Medications Given</label>
                        <textarea name="prescription" rows="2" placeholder="e.g. Amoxicillin 500mg 3x daily for 7 days" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('prescription') }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            @if (auth()->user()->hasPermission(\App\Enums\Permission::BillingManage))
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Billing handoff</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Treatment Fee</label>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 text-sm">₱</span>
                            <input type="number" name="treatment_fee" value="{{ old('treatment_fee') }}" step="0.01" placeholder="0.00" class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Saving adds this treatment to the billing work queue.</p>
                </div>
            </div>
            @endif

            <div class="flex flex-col gap-3">
                <a href="{{ route('records.index') }}">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-teal-500 hover:bg-teal-600 text-white font-semibold text-sm transition shadow-sm">
                    Save Record
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
