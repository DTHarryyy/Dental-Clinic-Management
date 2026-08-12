@php
    $categoryCount = count($chart['labels']);
    $isHorizontal = ($chart['orientation'] ?? 'vertical') === 'horizontal';
    $isCompact = (bool) ($chart['compact'] ?? false);
    $chartHeight = $isHorizontal
        ? min(360, max(180, 112 + ($categoryCount * (count($chart['datasets']) > 1 ? 42 : 34))))
        : ($isCompact ? 190 : 288);
@endphp
<article class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 {{ ($fillHeight ?? false) ? 'h-full' : '' }}" data-chart-layout="{{ $chart['layout'] ?? 'half' }}" data-chart-variant="{{ $chart['variant'] ?? 'distribution' }}">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="font-bold text-slate-800">{{ $chart['title'] }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ match ($chart['variant'] ?? '') { 'trend' => 'Change across the selected period', 'comparison' => 'Related measures shown together', 'ranking' => 'Demand ranked by category', default => 'Share of activity in this period' } }}</p>
        </div>
        <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ ucfirst($chart['variant'] ?? 'chart') }}</span>
    </div>
    <p class="sr-only" id="chart-summary-{{ $chartId }}">{{ $chart['summary'] }}</p>
    @if (collect($chart['datasets'])->sum(fn ($dataset) => collect($dataset['data'])->sum()) == 0)
        <div class="mt-4 flex min-h-32 flex-1 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-500">
            <div><i class="fa-regular fa-chart-bar mb-2 block text-xl text-slate-400" aria-hidden="true"></i>{{ $chart['empty'] ?? 'No data in this period.' }}</div>
        </div>
    @else
        <div class="report-chart-shell mt-4" style="--chart-height: {{ $chartHeight }}px"><canvas data-analytics-chart="{{ $chartId }}" role="img" aria-label="{{ $chart['title'] }} chart" aria-describedby="chart-summary-{{ $chartId }}"></canvas></div>
        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2" aria-label="{{ $chart['title'] }} legend">
            @if ($chart['type'] === 'doughnut')
                @foreach ($chart['labels'] as $labelIndex => $label)
                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-600"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ ['#10b981','#3b82f6','#8b5cf6','#ef4444','#f59e0b','#14b8a6'][$labelIndex % 6] }}"></span>{{ $label }}</span>
                @endforeach
            @else
                @foreach ($chart['datasets'] as $datasetIndex => $dataset)
                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-600"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ ['#10b981','#3b82f6','#8b5cf6','#ef4444'][$datasetIndex % 4] }}"></span>{{ $dataset['label'] }}</span>
                @endforeach
            @endif
        </div>
    @endif
</article>
