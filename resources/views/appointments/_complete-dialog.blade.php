{{--
    Completing an appointment IS filing its treatment record: this form posts to records.store
    with the appointment_id, and the controller flips the appointment to completed in the same
    transaction. Every field except the notes arrives prefilled from the row that opened it,
    injected by dialog-forms.js from the button's `fields` / `text` payload.
--}}
<form action="{{ route('records.store') }}" method="POST" data-ajax-form data-loading-text="Completing...">
    @csrf
    <input type="hidden" name="appointment_id" />
    <input type="hidden" name="patient_id" />

    <div class="space-y-6">
        {{-- Appointment being completed --}}
        <div class="rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3.5 flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-white text-blue-600 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-calendar-check text-sm"></i>
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-sm text-slate-800 truncate" data-fill-text="patient_name"></p>
                <p class="text-xs text-slate-600 mt-0.5" data-fill-text="appointment_summary"></p>
            </div>
        </div>

        {{-- Treatment --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Treatment Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Treatment <span class="text-red-500">*</span></label>
                    <input type="date" name="treatment_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Attending Dentist</label>
                    <select name="dentist_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                        <option value="">— Select dentist —</option>
                        @foreach ($dentists as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Service / Procedure <span class="text-red-500">*</span></label>
                    <select name="procedure" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required>
                        <option value="">— Select procedure —</option>
                        @foreach ($services as $svc)
                            <option value="{{ $svc->name }}">{{ $svc->name }}</option>
                        @endforeach
                    </select>
                    {{-- The booked service is a snapshot string; if it has since left the catalog there
                         is no matching option to preselect and the dentist has to pick a live one. --}}
                    <p class="text-xs text-slate-400 mt-1.5 hidden" data-procedure-missing>
                        The booked service <span class="font-semibold" data-fill-text="booked_service"></span> is no longer in the catalog. Please choose a current one.
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tooth / Area Treated</label>
                    <input type="text" name="tooth_area" placeholder="e.g. Upper molar #16" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Next Appointment</label>
                    <input type="date" name="next_appointment_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Clinical Notes <span class="text-red-500">*</span></label>
                    <textarea name="clinical_notes" rows="4" placeholder="Describe the procedure, findings, medications given, and follow-up instructions..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none" required></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Prescription / Medications Given</label>
                    <textarea name="prescription" rows="2" placeholder="e.g. Amoxicillin 500mg 3x daily for 7 days" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none"></textarea>
                </div>
            </div>
        </div>

        {{-- Billing --}}
        <div class="rounded-2xl border border-violet-100 bg-violet-50/50 p-4">
            <div class="flex items-start gap-3 mb-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm"><i class="fa-solid fa-file-invoice-dollar text-sm"></i></span>
                <div>
                    <h3 class="font-semibold text-sm text-slate-800">Billing</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Optionally create and email the invoice when this appointment is completed.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Treatment Fee</label>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 text-sm">₱</span>
                        <input type="number" name="treatment_fee" step="0.01" placeholder="0.00" class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                    </div>
                </div>
                <div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-violet-100 bg-white px-3 py-2.5 transition hover:border-violet-200">
                        <input type="checkbox" name="create_invoice" value="1" class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-200" />
                        <span>
                            <span class="block text-xs font-semibold text-slate-700">Create and email invoice</span>
                            <span class="mt-0.5 block text-[11px] leading-4 text-slate-500">Sends the invoice to the email saved on the patient record.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-6 pt-5 border-t border-slate-100">
        {{-- Escape hatch for consult-only visits with nothing clinical to file. Owned by the
             sibling form below via the `form` attribute, so it posts the plain status change. --}}
        <button type="submit" form="appointment-skip-record" class="text-xs text-slate-500 hover:text-slate-700 hover:underline transition">
            Mark complete without a record
        </button>
        <div class="flex items-center gap-3 ml-auto">
            <button type="button" x-on:click="open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                Cancel
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-semibold text-sm transition shadow-sm">
                <i class="fa-solid fa-check mr-1.5"></i>Save &amp; Complete
            </button>
        </div>
    </div>
</form>

<form id="appointment-skip-record" method="POST" action="" data-action-target="skip">
    @csrf
    <input type="hidden" name="status" value="completed" />
</form>
