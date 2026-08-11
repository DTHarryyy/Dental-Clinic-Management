@extends('layouts.app')
@section('page_title', 'New Appointment')

@section('content')
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('appointments.index') }}" class="hover:text-emerald-600 transition">Appointments</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">New Appointment</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Book Appointment</h1>
    <a href="{{ route('appointments.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="{{ route('appointments.store') }}" method="POST">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-6">

            {{-- Patient --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Patient Details</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Patient <span class="text-red-500">*</span></label>
                        <x-patient-lookup />
                    </div>
                    <p class="text-xs text-slate-400">
                        Walk-in? <a href="{{ route('patients.create') }}" class="text-emerald-600 font-medium hover:underline">Register new patient →</a>
                    </p>
                </div>
            </div>

            {{-- Schedule --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Schedule</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="appointment_date" value="{{ old('appointment_date') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Time <span class="text-red-500">*</span></label>
                        <select name="appointment_time" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">Select time slot</option>
                            @foreach (['09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '01:00 PM', '01:30 PM', '02:00 PM', '02:30 PM', '03:00 PM', '03:30 PM', '04:00 PM'] as $t)
                                <option {{ old('appointment_time') === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Dentist</label>
                        <select name="dentist_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">— Any available —</option>
                            @foreach ($dentists as $d)
                                <option value="{{ $d->id }}" {{ old('dentist_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Service --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Service</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Select Service <span class="text-red-500">*</span></label>
                        @if ($services->isNotEmpty())
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach ($services as $svc)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="service" value="{{ $svc }}" class="sr-only peer" {{ old('service') === $svc ? 'checked' : '' }} required />
                                        <div class="border border-slate-200 rounded-xl p-3 text-center text-xs font-semibold text-slate-600 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:bg-slate-50 transition">
                                            {{ $svc }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm px-4 py-3">
                                No services configured yet. <a href="{{ route('settings.services') }}" class="font-semibold underline">Add your services & pricing in Settings →</a>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes</label>
                        <textarea name="concern" rows="3" placeholder="Any special instructions or patient concerns..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('concern') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-4">Appointment Status</h3>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="pending" selected>Pending</option>
                    <option value="confirmed">Confirmed</option>
                </select>
                <p class="text-xs text-slate-400 mt-2">Set to "Confirmed" if the patient confirmed via call.</p>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('appointments.index') }}">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" @disabled($services->isEmpty()) class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Book Appointment
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
