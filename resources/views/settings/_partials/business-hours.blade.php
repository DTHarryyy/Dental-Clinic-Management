@include('settings._partials._flash')

<div class="settings-tab-body">
    <div class="space-y-5">
        <div class="responsive-card overflow-hidden">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="fa-solid fa-clock"></i></div><div><h2 class="font-semibold text-base text-slate-800">Business Hours</h2><p class="mt-0.5 text-xs text-slate-500">Used by patient booking and appointment confirmation.</p></div></div>
            <form action="{{ route('settings.hours.update') }}" method="POST" data-ajax-form data-loading-text="Saving..." class="p-6 space-y-6">@csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Lead time minutes</label><input type="number" name="booking_lead_minutes" value="{{ old('booking_lead_minutes', $clinic->booking_lead_minutes ?? 120) }}" min="0" max="1440" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Horizon days</label><input type="number" name="booking_horizon_days" value="{{ old('booking_horizon_days', $clinic->booking_horizon_days ?? 90) }}" min="1" max="365" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Slot interval</label><input type="number" name="slot_interval_minutes" value="{{ old('slot_interval_minutes', $clinic->slot_interval_minutes ?? 30) }}" min="5" max="120" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm"></div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <table class="responsive-stack-table w-full text-sm">
                        <thead><tr class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><th class="px-4 py-3 text-left">Day</th><th class="px-4 py-3 text-left">Open</th><th class="px-4 py-3 text-left">Morning</th><th class="px-4 py-3 text-left">Afternoon</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day => $label)
                                @php $hour = $hours[$day] ?? null; @endphp
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $label }}</td>
                                    <td class="px-4 py-3"><label class="inline-flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="hours[{{ $day }}][is_open]" value="1" @checked(old("hours.$day.is_open", $hour?->is_open ?? true)) class="rounded border-slate-300 text-emerald-600"> Open</label></td>
                                    <td class="px-4 py-3"><div class="flex gap-2"><input type="time" name="hours[{{ $day }}][morning_opens_at]" value="{{ old("hours.$day.morning_opens_at", $hour?->morning_opens_at ? substr($hour->morning_opens_at,0,5) : '08:00') }}" class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm"><input type="time" name="hours[{{ $day }}][morning_closes_at]" value="{{ old("hours.$day.morning_closes_at", $hour?->morning_closes_at ? substr($hour->morning_closes_at,0,5) : '12:00') }}" class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm"></div></td>
                                    <td class="px-4 py-3"><div class="flex gap-2"><input type="time" name="hours[{{ $day }}][afternoon_opens_at]" value="{{ old("hours.$day.afternoon_opens_at", $hour?->afternoon_opens_at ? substr($hour->afternoon_opens_at,0,5) : '13:00') }}" class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm"><input type="time" name="hours[{{ $day }}][afternoon_closes_at]" value="{{ old("hours.$day.afternoon_closes_at", $hour?->afternoon_closes_at ? substr($hour->afternoon_closes_at,0,5) : '17:00') }}" class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm"></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-slate-100 pt-5"><button class="primary-action"><i class="fa-solid fa-floppy-disk"></i> Save hours</button></div>
            </form>
        </div>
    </div>
</div>
