<form action="{{ route('appointments.store') }}" method="POST" data-ajax-form data-loading-text="Booking...">
    @csrf

    <div class="space-y-6">
        {{-- Patient --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Patient Details</h3>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Patient <span class="text-red-500">*</span></label>
                <select name="patient_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required>
                    <option value="">{{ $patients->isEmpty() ? '— No patients registered yet —' : '— Choose patient —' }}</option>
                    @foreach ($patients as $p)
                        <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-slate-400 mt-2">
                Walk-in? <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'patient-create' } }))" class="text-emerald-600 font-medium hover:underline">Register new patient →</button>
            </p>
        </div>

        {{-- Schedule --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Schedule</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="appointment_date" value="{{ old('appointment_date') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Time</label>
                    <select name="appointment_time" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                        <option value="">Select time slot</option>
                        @foreach (['09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '01:00 PM', '01:30 PM', '02:00 PM', '02:30 PM', '03:00 PM', '03:30 PM', '04:00 PM'] as $t)
                            <option {{ old('appointment_time') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
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
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Service</h3>
            <div class="space-y-4">
                <div data-field-group="service">
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
                    <textarea name="concern" rows="2" placeholder="Any special instructions or patient concerns..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('concern') }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 rounded-xl p-4">
            <label class="block text-sm font-medium text-slate-700 mb-1">Appointment Status</label>
            <select name="status" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <option value="pending" selected>Pending</option>
                <option value="confirmed">Confirmed</option>
            </select>
            <p class="text-xs text-slate-400 mt-2">Set to "Confirmed" if the patient confirmed via call.</p>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-slate-100">
        <button type="button" x-on:click="open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
            Cancel
        </button>
        <button type="submit" @disabled($services->isEmpty()) class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
            Book Appointment
        </button>
    </div>
</form>
