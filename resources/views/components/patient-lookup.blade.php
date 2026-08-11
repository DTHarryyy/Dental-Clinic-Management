@props(['name' => 'patient_id', 'value' => null, 'label' => null])

<div data-patient-lookup data-lookup-url="{{ route('lookups.patients') }}" class="relative">
    <input type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}" data-patient-id>
    <input type="search" autocomplete="off" placeholder="Search patient name or email…" data-patient-search
           @if($label) value="{{ $label }}" @endif
           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
    <div data-patient-results class="absolute z-30 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl"></div>
    <p class="mt-1 text-xs text-slate-400">Type to search; at most 20 matching active patients are loaded.</p>
</div>
