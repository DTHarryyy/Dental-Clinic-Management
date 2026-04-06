@extends('layouts.app')
@section('page_title', 'New Appointment')

@section('content')
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="/appointments" class="hover:text-emerald-600 transition">Appointments</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">New Appointment</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Book Appointment</h1>
    <a href="/appointments" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="#" method="POST">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-6">

            {{-- Patient --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Patient Details</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Patient <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">— Choose patient —</option>
                            <option>Maria Santos</option>
                            <option>Jose Dela Cruz</option>
                            <option>Ana Reyes</option>
                            <option>Luis Gomez</option>
                            <option>Rosa Bautista</option>
                        </select>
                    </div>
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 hidden" id="patient-preview">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-emerald-200 text-emerald-700 flex items-center justify-center font-bold text-sm">MS</div>
                            <div>
                                <div class="font-semibold text-sm text-slate-800">Maria Santos</div>
                                <div class="text-xs text-slate-500">0917-111-1111 · Last visit: Mar 22, 2026</div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">
                        Walk-in? <a href="/patients/create" class="text-emerald-600 font-medium hover:underline">Register new patient →</a>
                    </p>
                </div>
            </div>

            {{-- Schedule --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Schedule</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                        <input type="date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Time <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">Select time slot</option>
                            @foreach (['09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '01:00 PM', '01:30 PM', '02:00 PM', '02:30 PM', '03:00 PM', '03:30 PM', '04:00 PM'] as $t)
                                <option>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Dentist <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option selected>Dr. Reyes</option>
                            <option>Dr. Lim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Duration</label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option>30 minutes</option>
                            <option>1 hour</option>
                            <option>1.5 hours</option>
                            <option>2 hours</option>
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
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @php
                                $services = ['Consultation', 'Teeth Cleaning', 'Dental Filling', 'Tooth Extraction', 'Root Canal', 'Orthodontics', 'Teeth Whitening', 'X-Ray', 'Dentures'];
                            @endphp
                            @foreach ($services as $svc)
                                <label class="cursor-pointer">
                                    <input type="radio" name="service" class="sr-only peer" />
                                    <div class="border border-slate-200 rounded-xl p-3 text-center text-xs font-semibold text-slate-600 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:bg-slate-50 transition">
                                        {{ $svc }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes</label>
                        <textarea rows="3" placeholder="Any special instructions or patient concerns..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-4">Appointment Status</h3>
                <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="pending" selected>Pending</option>
                    <option value="confirmed">Confirmed</option>
                </select>
                <p class="text-xs text-slate-400 mt-2">Set to "Confirmed" if the patient confirmed via call.</p>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
                <h3 class="font-semibold text-sm text-blue-700 mb-1"><i class="fa-solid fa-calendar-days mr-1"></i> Today's Schedule</h3>
                <p class="text-xs text-blue-600">5 appointments booked. Next available: 02:30 PM</p>
            </div>

            <div class="flex flex-col gap-3">
                <a href="/appointments">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    Book Appointment
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
