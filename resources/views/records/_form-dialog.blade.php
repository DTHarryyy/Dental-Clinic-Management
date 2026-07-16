<form action="{{ route('records.store') }}" method="POST" data-ajax-form data-loading-text="Saving...">
    @csrf

    <div class="space-y-6">
        {{-- Patient --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Patient</h3>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Patient <span class="text-red-500">*</span></label>
                <select name="patient_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required>
                    <option value="">{{ $patients->isEmpty() ? '— No patients registered yet —' : '— Choose patient —' }}</option>
                    @foreach ($patients as $p)
                        <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-2">
                    No patient in the list? <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'patient-create' } }))" class="text-emerald-600 font-medium hover:underline">Register a new patient →</button>
                </p>
            </div>
        </div>

        {{-- Treatment --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Treatment Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Treatment <span class="text-red-500">*</span></label>
                    <input type="date" name="treatment_date" value="{{ old('treatment_date', date('Y-m-d')) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Attending Dentist</label>
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
                        <p class="text-xs text-amber-600 mt-1">No services configured yet. <a href="{{ route('settings.services') }}" class="font-semibold underline">Add them in Settings →</a></p>
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
                    <textarea name="clinical_notes" rows="4" placeholder="Describe the procedure, findings, medications given, and follow-up instructions..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none" required>{{ old('clinical_notes') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Prescription / Medications Given</label>
                    <textarea name="prescription" rows="2" placeholder="e.g. Amoxicillin 500mg 3x daily for 7 days" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('prescription') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Billing --}}
        <div class="bg-slate-50 rounded-xl p-4">
            <h3 class="font-semibold text-sm text-slate-800 mb-3">Billing</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Treatment Fee</label>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 text-sm">₱</span>
                        <input type="number" name="treatment_fee" value="{{ old('treatment_fee') }}" step="0.01" placeholder="0.00" class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                    </div>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="create_invoice" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                        <span class="text-xs text-slate-600">Create invoice automatically</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-slate-100">
        <button type="button" x-on:click="open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
            Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-teal-500 hover:bg-teal-600 text-white font-semibold text-sm transition shadow-sm">
            Save Record
        </button>
    </div>
</form>
