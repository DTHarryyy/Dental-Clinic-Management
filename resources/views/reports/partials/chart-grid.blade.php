@if (count($charts))
    <section @class(['mb-6' => $activeTab !== 'overview']) aria-labelledby="report-visuals-heading" data-report-visuals>
        <h3 id="report-visuals-heading" class="sr-only">Report visualizations</h3>
        @if ($activeTab === 'financial')
            @php
                $wideChart = collect($charts)->first(fn ($chart) => ($chart['layout'] ?? '') === 'wide');
                $wideId = collect($charts)->search(fn ($chart) => ($chart['layout'] ?? '') === 'wide');
                $supporting = collect($charts)->except([$wideId]);
            @endphp
            <div class="grid min-w-0 grid-cols-1 gap-4 xl:grid-cols-12 xl:items-stretch">
                <div class="min-w-0 xl:col-span-8">
                    @include('reports.partials.chart', ['chartId' => $wideId, 'chart' => $wideChart, 'fillHeight' => true])
                </div>
                <div class="grid min-w-0 gap-4 sm:grid-cols-2 xl:col-span-4 xl:grid-cols-1">
                    @foreach ($supporting as $chartId => $chart)
                        @include('reports.partials.chart', compact('chartId', 'chart'))
                    @endforeach
                </div>
            </div>
        @else
            <div class="grid min-w-0 grid-cols-1 gap-4 xl:grid-cols-12">
                @foreach ($charts as $chartId => $chart)
                    @php
                        $span = match ($chart['layout'] ?? 'half') {
                            'full' => 'xl:col-span-12',
                            'wide' => 'xl:col-span-8',
                            'compact' => 'xl:col-span-4',
                            default => 'xl:col-span-6',
                        };
                    @endphp
                    <div class="min-w-0 {{ $span }}">
                        @include('reports.partials.chart', compact('chartId', 'chart'))
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@else
    <div @class(['rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500', 'mb-6' => $activeTab !== 'overview'])>No visual data is available for this section.</div>
@endif
