@extends('layouts.patient')
@section('page_title', 'Book Appointment')

@section('content')
@php $err = fn ($field) => $errors->has($field) ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200'; @endphp
<div class="page-header">
    <div><h1 class="page-title">Book Appointment</h1><p class="page-subtitle">Choose services, an exact available time, then submit for confirmation.</p></div>
    <a href="{{ route('patient.appointments.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-2"></i>Appointments</a>
</div>

<form action="{{ route('patient.appointments.store') }}" method="POST" data-patient-booking data-dates-url="{{ route('patient.appointments.dates') }}" data-slots-url="{{ route('patient.appointments.slots') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="requested_start_at" value="{{ old('requested_start_at') }}" data-booking-start>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><i class="fa-solid fa-circle-exclamation mr-1.5"></i>Please fix the highlighted fields below.</div>
    @endif

    <div class="grid gap-3 sm:grid-cols-3">
        @foreach(['Services', 'Date & Time', 'Review'] as $step)
            <div class="rounded-2xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700"><span class="mr-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-xs text-emerald-700">{{ $loop->iteration }}</span>{{ $step }}</div>
        @endforeach
    </div>

    <section class="responsive-card responsive-card-padding">
        <h2 class="font-bold text-slate-800">Services</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($services as $service)
                <label class="cursor-pointer">
                    <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" data-booking-service data-service-name="{{ $service->name }}" data-service-price="{{ $service->price }}" data-service-duration="{{ $service->duration_minutes }}" @checked(in_array($service->id, old('service_ids', []))) class="peer sr-only">
                    <span class="block rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-emerald-300 peer-checked:border-emerald-300 peer-checked:bg-emerald-50">
                        <span class="block font-semibold text-slate-800">{{ $service->name }}</span>
                        <span class="mt-1 block text-xs text-slate-500">{{ $service->duration_minutes }} min · PHP {{ number_format($service->price, 2) }}</span>
                    </span>
                </label>
            @empty
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Online booking is unavailable until services are configured.</div>
            @endforelse
        </div>
        @error('service_ids') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </section>

    <section class="responsive-card responsive-card-padding">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="font-bold text-slate-800">Date & Time</h2><p class="text-sm text-slate-500">Use the arrows to move one week at a time.</p></div>
            <div class="flex items-center gap-2">
                <button type="button" data-week-prev class="touch-target rounded-xl border border-slate-200 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700" aria-label="Previous week"><i class="fa-solid fa-chevron-left"></i></button>
                <span data-week-label class="min-w-36 text-center text-sm font-semibold text-slate-700">Loading</span>
                <input type="date" data-calendar-jump class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <button type="button" data-week-next class="touch-target rounded-xl border border-slate-200 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700" aria-label="Next week"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
        <div data-date-strip class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7"></div>
        <div class="mt-5">
            <h3 class="text-sm font-semibold text-slate-700">Available times</h3>
            <div data-time-slots class="mt-3 grid gap-2 sm:grid-cols-3 lg:grid-cols-4"></div>
            <p data-booking-live class="mt-2 text-xs text-slate-500" aria-live="polite">Choose at least one service to load availability.</p>
            @error('requested_start_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </section>

    <section class="responsive-card responsive-card-padding">
        <h2 class="font-bold text-slate-800">Concern</h2>
        <textarea name="concern" rows="3" maxlength="3000" placeholder="Briefly describe your dental concern or reason for visit." class="mt-4 w-full resize-none rounded-xl border {{ $err('concern') }} bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">{{ old('concern') }}</textarea>
        @error('concern') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </section>

    <section class="responsive-card responsive-card-padding">
        <h2 class="font-bold text-slate-800">Review</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><p class="text-xs font-semibold text-slate-500">Services</p><p data-review-services class="mt-1 text-sm font-bold text-slate-800">None selected</p></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><p class="text-xs font-semibold text-slate-500">Duration</p><p data-review-duration class="mt-1 text-sm font-bold text-slate-800">0 min</p></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><p class="text-xs font-semibold text-slate-500">Estimate</p><p data-review-total class="mt-1 text-sm font-bold text-slate-800">PHP 0.00</p></div>
        </div>
        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
            <p class="font-semibold text-slate-800">{{ $patient->name }}</p>
            <p class="text-slate-500">{{ $patient->email }} · {{ $patient->mobile ?: 'No mobile number yet' }}</p>
        </div>
        <div class="mt-5 flex justify-end"><button type="submit" @disabled($services->isEmpty()) class="primary-action w-full sm:w-auto"><i class="fa-solid fa-paper-plane"></i> Submit request</button></div>
    </section>
</form>
@endsection
