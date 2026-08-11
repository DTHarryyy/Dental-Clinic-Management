<div x-data="{ open:false, action:'', name:'', when:'', reschedule:false, preferredDate:'', availabilityUrl:'', duration:30, openCancel(a){ this.action=a.action; this.name=a.name ?? 'this patient'; this.when=a.when ?? ''; this.preferredDate=a.preferredDate ?? ''; this.availabilityUrl=a.availabilityUrl ?? ''; this.duration=a.duration ?? 30; this.reschedule=false; this.open=true } }"
     x-on:open-appointment-cancel.window="openCancel($event.detail)" x-on:keydown.escape.window="if(open) open=false" x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4" style="display:none">
    <div class="absolute inset-0 bg-slate-900/50" x-on:click="open=false"></div>
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl" x-on:click.stop>
        <div class="p-6">
            <div class="mb-4 flex items-center gap-3"><div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-500"><i class="fa-solid fa-calendar-xmark"></i></div><div><h2 class="text-lg font-bold text-slate-800">Cancel appointment</h2><p class="text-sm text-slate-500"><span class="font-semibold" x-text="name"></span> <span x-text="when"></span></p></div></div>
            <form :action="action" method="POST" data-ajax-form data-cancel-reschedule data-loading-text="Cancelling..." class="space-y-5">
                @csrf
                <input type="hidden" name="status" value="cancelled">
                <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Reason for cancellation <span class="text-red-500">*</span></label><textarea name="cancellation_reason" rows="3" minlength="3" maxlength="1000" required placeholder="Explain why the appointment is being cancelled. This will be included in the patient email." class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"></textarea></div>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"><input type="checkbox" name="reschedule_requested" value="1" x-model="reschedule" class="mt-1 rounded"><span><span class="block text-sm font-semibold text-slate-700">Patient needs another schedule</span><span class="text-xs text-slate-500">Create a new pending request while keeping this cancellation on file.</span></span></label>
                <div x-show="reschedule" class="grid gap-4 sm:grid-cols-2">
                    <input type="hidden" name="availability_url" :value="availabilityUrl">
                    <div><label class="mb-1.5 block text-sm font-medium">New date</label><input type="date" name="reschedule_date" x-model="preferredDate" :required="reschedule" min="{{ today()->toDateString() }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium">Duration</label><select name="reschedule_duration_minutes" x-model="duration" :required="reschedule" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">@foreach(range(30, 240, 30) as $minutes)<option value="{{ $minutes }}">{{ $minutes }} minutes</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Exact new time</label><select name="reschedule_start_at" :required="reschedule" disabled class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"><option value="">Choose a date first</option></select><p data-reschedule-status class="mt-1 text-xs text-slate-500"></p></div>
                </div>
                <div class="rounded-xl bg-blue-50 p-3 text-xs text-blue-800"><i class="fa-solid fa-envelope mr-1"></i>The patient will be emailed the cancellation reason and any new requested schedule.</div>
                <div class="flex gap-3 border-t pt-4"><button type="button" x-on:click="open=false" class="flex-1 rounded-xl border px-5 py-2.5 text-sm font-semibold">Keep appointment</button><button type="submit" class="flex-1 rounded-xl bg-red-500 px-5 py-2.5 text-sm font-semibold text-white">Cancel &amp; notify</button></div>
            </form>
        </div>
    </div>
</div>
