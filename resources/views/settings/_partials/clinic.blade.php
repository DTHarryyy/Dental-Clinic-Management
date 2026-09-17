@include('settings._partials._flash')

<div class="settings-tab-body">
    <div class="space-y-5">
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
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Booking Lead Time</label>
                        <input type="number" name="booking_lead_minutes" value="{{ old('booking_lead_minutes', $clinic->booking_lead_minutes ?? 120) }}" min="0" max="1440" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                        <p class="mt-1.5 text-xs text-slate-500">Minutes required before patients can book.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Booking Horizon</label>
                        <input type="number" name="booking_horizon_days" value="{{ old('booking_horizon_days', $clinic->booking_horizon_days ?? 90) }}" min="1" max="365" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                        <p class="mt-1.5 text-xs text-slate-500">Days ahead patients can request.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Slot Interval</label>
                        <input type="number" name="slot_interval_minutes" value="{{ old('slot_interval_minutes', $clinic->slot_interval_minutes ?? 30) }}" min="5" max="120" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
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
