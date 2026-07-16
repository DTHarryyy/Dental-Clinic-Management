@props(['label'])

{{-- Read-only label/value pair used in detail views. Pass the value as the slot; add
     class="sm:col-span-2" for full-width fields. --}}
<div {{ $attributes->merge(['class' => '']) }}>
    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
    <dd class="mt-1 text-sm font-medium text-slate-800 break-words">{{ $slot }}</dd>
</div>
