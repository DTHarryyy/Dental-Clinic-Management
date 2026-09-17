@include('settings._partials._flash')

<div class="settings-tab-body">
    <div class="space-y-5">
        <div class="responsive-card responsive-card-padding">
            <h2 class="font-bold text-slate-800">Add closure</h2>
            <form action="{{ route('settings.closures.store') }}" method="POST" data-ajax-form data-loading-text="Adding..." class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">@csrf
                <input type="date" name="closure_date" required class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm"><input type="checkbox" name="is_full_day" value="1" checked class="rounded border-slate-300 text-emerald-600"> Full day</label>
                <input type="time" name="starts_at" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <input type="time" name="ends_at" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <input name="reason" required placeholder="Reason" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <div class="sm:col-span-2 lg:col-span-5"><button class="primary-action"><i class="fa-solid fa-plus"></i> Add closure</button></div>
            </form>
        </div>
        <div class="responsive-card overflow-hidden">
            <table class="responsive-stack-table w-full text-sm"><thead><tr class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><th class="px-5 py-3 text-left">Date</th><th class="px-5 py-3 text-left">Range</th><th class="px-5 py-3 text-left">Reason</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($closures as $closure)<tr><td class="px-5 py-4 font-semibold text-slate-800">{{ $closure->closure_date->format('M j, Y') }}</td><td class="px-5 py-4 text-slate-600">{{ $closure->is_full_day ? 'Full day' : substr($closure->starts_at,0,5).' - '.substr($closure->ends_at,0,5) }}</td><td class="px-5 py-4 text-slate-600">{{ $closure->reason }}</td><td class="px-5 py-4 text-right"><form action="{{ route('settings.closures.destroy', $closure) }}" method="POST" data-ajax-form data-loading-text="Removing...">@csrf @method('DELETE')<button class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600">Remove</button></form></td></tr>@empty<tr><td colspan="4" class="px-5 py-12 text-center text-slate-400">No closures configured.</td></tr>@endforelse</tbody></table>
            @if($closures->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $closures->links() }}</div>@endif
        </div>
    </div>
</div>
