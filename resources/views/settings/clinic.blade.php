@extends('layouts.app')
@section('page_title', 'Settings — Clinic Info')

@section('content')
{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Settings</h1>
    <p class="text-slate-500 text-sm mt-0.5">Configure your clinic preferences and system options</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

    {{-- Sub-nav --}}
    <div class="xl:col-span-1">
        @include('settings._nav')
    </div>

    {{-- Content --}}
    <div class="xl:col-span-3">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
                <div class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <i class="fa-solid fa-hospital"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-base text-slate-800">Clinic Information</h2>
                    <p class="text-xs text-slate-500 mt-0.5">The single source of truth for receipts, invoices, patient emails, and the public booking page</p>
                </div>
            </div>

            <form action="{{ route('settings.clinic.update') }}" method="POST" data-ajax-form data-loading-text="Saving..." class="p-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Clinic Name <span class="text-red-500">*</span></label>
                        <input type="text" name="clinic_name" value="{{ old('clinic_name', $clinic->clinic_name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                        <input type="tel" name="phone" value="{{ old('phone', $clinic->phone) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Patient Reply Email</label>
                        <input type="email" name="email" value="{{ old('email', $clinic->email) }}" placeholder="clinic@example.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                        <p class="mt-1.5 text-xs leading-5 text-slate-500">All appointment, reminder, invoice, and receipt emails use this as their Reply-To address. Patients see the clinic domain as the sender, but replies arrive here.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                        <input type="text" name="address" value="{{ old('address', $clinic->address) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tax ID / TIN</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id', $clinic->tax_id) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Website</label>
                        <input type="url" name="website" value="{{ old('website', $clinic->website) }}" placeholder="https://" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>

                <div class="flex justify-end mt-6 pt-5 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                        <i class="fa-solid fa-floppy-disk"></i> Save Clinic Info
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
