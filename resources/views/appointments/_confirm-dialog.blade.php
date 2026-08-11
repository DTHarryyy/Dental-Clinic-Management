<form action="" method="POST" data-action-target="confirm" data-appointment-confirm data-ajax-form data-loading-text="Confirming...">
    @csrf
    <input type="hidden" name="status" value="confirmed"><input type="hidden" name="availability_url">
    <div class="space-y-5">
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-800">The booking will be linked to a matching patient automatically. If no patient matches, a new patient profile will be created.</div>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="rounded-xl border p-3 text-sm"><input type="radio" name="scheduling_mode" value="exact" checked class="mr-2">Exact schedule</label>
            <label class="rounded-xl border p-3 text-sm"><input type="radio" name="scheduling_mode" value="first_come" class="mr-2">First come, first served</label>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-1.5 block text-sm font-medium">Dentist <span class="text-red-500">*</span></label><select name="dentist_id" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" required><option value="">Select dentist</option>@foreach($dentists as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div><label class="mb-1.5 block text-sm font-medium">Schedule date <span class="text-red-500">*</span></label><input type="date" name="schedule_date" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" required></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-1.5 block text-sm font-medium">Start time <span class="text-red-500">*</span></label><select name="scheduled_start_at" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" required disabled><option value="">Choose dentist and date first</option></select><p data-slot-status class="mt-1 text-xs text-slate-500"></p></div>
            <div data-exact-duration><label class="mb-1.5 block text-sm font-medium">Duration</label><select name="duration_minutes" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">@foreach(range(30, 240, 30) as $minutes)<option value="{{ $minutes }}">{{ $minutes }} minutes</option>@endforeach</select></div>
            <div data-session-end class="hidden"><label class="mb-1.5 block text-sm font-medium">End date and time <span class="text-red-500">*</span></label><input type="datetime-local" name="session_end_at" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"><p class="mt-1 text-xs text-slate-500">Patients are served in booking order.</p></div>
        </div>
        <label class="flex gap-2 text-sm text-amber-800"><input type="checkbox" name="preference_change_acknowledged" value="1" class="mt-1"> I contacted the patient and acknowledge this may be outside their preferred date or window.</label>
        <div data-priority-warning class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3"><p class="text-sm font-semibold text-amber-900">Older requests are waiting for this preference window.</p><p data-older-requests class="mt-1 text-xs text-amber-800"></p><label class="mt-2 block text-xs font-medium">Override reason</label><textarea name="priority_override_reason" rows="2" class="mt-1 w-full rounded-lg border border-amber-200 px-3 py-2 text-sm"></textarea></div>
        <div class="flex justify-end gap-3 border-t pt-4"><button type="button" x-on:click="open=false" class="rounded-xl border px-5 py-2.5 text-sm font-semibold">Cancel</button><button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white">Confirm schedule</button></div>
    </div>
</form>
