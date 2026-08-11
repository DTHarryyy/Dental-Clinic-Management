<form action="{{ route('appointments.store') }}" method="POST" data-ajax-form data-loading-text="Booking..." data-public-booking data-availability-url="{{ route('public.book.availability') }}">
    @csrf
    <input type="hidden" name="preferred_time_window" value="morning">
    <div class="space-y-6">
        <div>
            <h3 class="mb-4 border-b border-slate-100 pb-3 text-sm font-semibold text-slate-800">Patient Details</h3>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Select Patient <span class="text-red-500">*</span></label>
            <x-patient-lookup />
        </div>
        <div>
            <h3 class="mb-4 border-b border-slate-100 pb-3 text-sm font-semibold text-slate-800">Preference</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1.5 block text-sm font-medium">Preferred date</label><input type="date" name="preferred_date" value="{{ old('preferred_date') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" required></div>
                <div><label class="mb-1.5 block text-sm font-medium">Exact time</label><select name="requested_start_at" required disabled class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"><option value="">Choose date and services first</option></select><p data-public-slot-status class="mt-1 text-xs text-slate-500"></p></div>
            </div>
        </div>
        <div>
            <h3 class="mb-4 border-b border-slate-100 pb-3 text-sm font-semibold text-slate-800">Services <span class="font-normal text-slate-400">(select all needed)</span></h3>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($services as $svc)
                    <label class="cursor-pointer"><input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" class="peer sr-only"><div class="rounded-xl border border-slate-200 p-3 text-center text-xs font-semibold text-slate-600 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">{{ $svc->name }}<span class="mt-1 block font-normal">{{ $svc->duration_minutes }} min</span></div></label>
                @endforeach
            </div>
        </div>
        <div><label class="mb-1.5 block text-sm font-medium">Notes</label><textarea name="concern" rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">{{ old('concern') }}</textarea></div>
        <div class="rounded-xl bg-slate-50 p-4 text-xs text-slate-500">This exact requested range is held while the appointment is pending. Assign a dentist when confirming it.</div>
    </div>
    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5"><button type="button" x-on:click="open=false" class="rounded-xl border px-5 py-2.5 text-sm font-semibold">Cancel</button><button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white">Book Appointment</button></div>
</form>
