@if (count($insights))
    <section class="mb-5" aria-labelledby="report-insights-heading" data-report-insights>
        <div class="mb-3 flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></span>
            <h3 id="report-insights-heading" class="font-bold text-slate-800">Key insights</h3>
        </div>
        <div class="grid grid-cols-1 gap-3 min-[380px]:grid-cols-2 lg:grid-cols-4 {{ count($insights) === 5 ? 'xl:grid-cols-5' : '' }}">
            @foreach ($insights as $insight)
                <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><i class="fa-solid {{ $insight['icon'] ?? 'fa-chart-simple' }}" aria-hidden="true"></i></span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $insight['label'] }}</p>
                            <p class="mt-1 break-words text-base font-bold leading-snug text-slate-900">{{ $insight['value'] }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $insight['context'] ?? '' }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
