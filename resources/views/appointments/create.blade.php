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

<form action="{{ route('appointments.store') }}" method="POST" data-public-booking data-availability-url="{{ route('public.book.availability') }}">
    @csrf
    <input type="hidden" name="preferred_time_window" value="{{ old('preferred_time_window', 'morning') }}">
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
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Preferred date <span class="text-red-500">*</span></label>
                        <input type="date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ today()->toDateString() }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Exact time <span class="text-red-500">*</span></label>
                        <select name="requested_start_at" required disabled class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">Choose date and services first</option>
                        </select>
                        <p data-public-slot-status class="mt-1 text-xs text-slate-500"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Dentist</label>
                        <select name="dentist_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">— Assign later —</option>
                            @foreach ($dentists as $d)
                                <option value="{{ $d->id }}" {{ old('dentist_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Service --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Services <span class="font-normal text-slate-400">(select all needed)</span></h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Select Service(s) <span class="text-red-500">*</span></label>
                        @if ($services->isNotEmpty())
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach ($services as $svc)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="sr-only peer" {{ in_array($svc->id, (array) old('service_ids', [])) ? 'checked' : '' }} />
                                        <div class="border border-slate-200 rounded-xl p-3 text-center text-xs font-semibold text-slate-600 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:bg-slate-50 transition">
                                            {{ $svc->name }}
                                            <span class="mt-1 block font-normal">{{ $svc->duration_minutes }} min</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm px-4 py-3">
                                No services configured yet. @can('settings.view')<a href="{{ route('settings.services') }}" class="font-semibold underline">Add your services & pricing in Settings →</a>@else Contact an administrator.@endcan
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
                <p class="text-sm font-medium text-slate-600">Pending</p>
                <p class="text-xs text-slate-400 mt-2">New appointments start pending. Confirm it (and assign a dentist) from the Appointments list once it's ready.</p>
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
