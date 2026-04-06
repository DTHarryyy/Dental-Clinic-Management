@extends('layouts.app')
@section('page_title', 'Add Treatment Record')

@section('content')
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="/records" class="hover:text-emerald-600 transition">Dental Records</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Add Record</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add Treatment Record</h1>
    <a href="/records" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</a>
</div>

<form action="#" method="POST">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">

            {{-- Patient --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Patient</h2>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Patient <span class="text-red-500">*</span></label>
                    <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                        <option value="">— Choose patient —</option>
                        <option>Maria Santos</option>
                        <option>Jose Dela Cruz</option>
                        <option>Ana Reyes</option>
                        <option>Luis Gomez</option>
                    </select>
                </div>
            </div>

            {{-- Treatment --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Treatment Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Treatment <span class="text-red-500">*</span></label>
                        <input type="date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Attending Dentist <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option selected>Dr. Reyes</option>
                            <option>Dr. Lim</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Service / Procedure <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">— Select procedure —</option>
                            @foreach (['Consultation', 'Teeth Cleaning', 'Dental Filling', 'Tooth Extraction', 'Root Canal', 'Orthodontics', 'Teeth Whitening', 'X-Ray', 'Dentures', 'Crown / Bridge', 'Implant'] as $svc)
                                <option>{{ $svc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tooth / Area Treated</label>
                        <input type="text" placeholder="e.g. Upper molar #16" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Next Appointment</label>
                        <input type="date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Clinical Notes <span class="text-red-500">*</span></label>
                        <textarea rows="5" placeholder="Describe the procedure, findings, medications given, and follow-up instructions..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Prescription / Medications Given</label>
                        <textarea rows="2" placeholder="e.g. Amoxicillin 500mg 3x daily for 7 days" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none"></textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Billing</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Treatment Fee</label>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 text-sm">₱</span>
                            <input type="number" placeholder="0.00" class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                            <span class="text-xs text-slate-600">Create invoice automatically</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Attachments</h3>
                <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center">
                    <div class="text-3xl mb-2"><i class="fa-solid fa-paperclip text-slate-400"></i></div>
                    <p class="text-xs text-slate-500">X-rays, photos, or documents</p>
                    <button type="button" class="mt-3 text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Choose Files</button>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a href="/records">
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
