@php $kpiCount = count($kpis); @endphp
<div class="mb-5 grid grid-cols-1 gap-3 min-[380px]:grid-cols-2 xl:grid-cols-12" data-report-kpis="{{ $kpiCount }}">
    @foreach ($kpis as $kpi)
        @php
            $desktopSpan = match (true) {
                $kpiCount === 7 && $loop->iteration > 4 => 'xl:col-span-4',
                $kpiCount === 6 => 'xl:col-span-4',
                $kpiCount <= 4 => 'xl:col-span-3',
                default => 'xl:col-span-3',
            };
        @endphp
        <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 {{ $desktopSpan }}">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
            <p class="mt-2 break-words text-2xl font-bold text-slate-900">{{ $kpi['value'] }}</p>
            <p class="mt-2 flex items-start gap-1.5 text-xs text-slate-500">
                @if ($kpi['comparison'])
                    @php
                        $comparisonColor = $kpi['direction'] > 0 ? 'text-emerald-700' : ($kpi['direction'] < 0 ? 'text-red-700' : 'text-slate-600');
                        $comparisonIcon = $kpi['arrow'] === 'up' ? 'fa-arrow-trend-up' : ($kpi['arrow'] === 'down' ? 'fa-arrow-trend-down' : 'fa-minus');
                    @endphp
                    <i class="fa-solid {{ $comparisonIcon }} mt-0.5 {{ $comparisonColor }}" aria-hidden="true"></i>
                    <span><span @class(['font-bold', $comparisonColor])>{{ $kpi['comparison'] }}</span> {{ $kpi['context'] }}</span>
                @else
                    <span>{{ $kpi['context'] }}</span>
                @endif
            </p>
        </article>
    @endforeach
</div>
