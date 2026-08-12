@php
    $maximum = max(collect($items)->max('raw') ?? 0, 1);
@endphp
<div class="space-y-3" data-ranked-summary>
    @forelse ($items as $item)
        @php $progress = min(100, max(0, ((float) $item['raw'] / $maximum) * 100)); @endphp
        <div>
            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                <span class="min-w-0 truncate font-medium text-slate-700" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                <strong class="shrink-0 text-slate-900">{{ $item['value'] }}</strong>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="meter" aria-label="{{ $item['label'] }}" aria-valuemin="0" aria-valuemax="{{ $maximum }}" aria-valuenow="{{ $item['raw'] }}">
                <div class="report-progress h-full rounded-full bg-emerald-500" style="--progress: {{ $progress }}%"></div>
            </div>
            @if (! empty($item['context']))<p class="mt-1 text-[11px] text-slate-500">{{ $item['context'] }}</p>@endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">{{ $empty }}</div>
    @endforelse
</div>
