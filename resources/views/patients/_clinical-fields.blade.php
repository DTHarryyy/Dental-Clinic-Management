@php
    $value = fn (string $field, mixed $default = '') => old($field, $patient?->{$field} ?? $default);
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-teal-400 focus:outline-none focus:ring-4 focus:ring-teal-500/10';
    $selectedConditions = old('conditions', $patient?->conditions ?? []);
@endphp
<section>
    <h3 class="mb-4 text-sm font-semibold text-slate-800">Medical History</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label class="text-sm text-slate-600">Known allergies<input name="allergies" value="{{ $value('allergies') }}" class="{{ $input }} mt-1.5"></label>
        <label class="text-sm text-slate-600">Current medications<input name="medications" value="{{ $value('medications') }}" class="{{ $input }} mt-1.5"></label>
        <fieldset class="sm:col-span-2"><legend class="mb-2 text-sm text-slate-600">Medical conditions</legend><div class="grid grid-cols-2 gap-2 sm:grid-cols-3">@foreach(['Diabetes','Hypertension','Heart Disease','Asthma','Bleeding Disorder','None'] as $condition)<label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" name="conditions[]" value="{{ $condition }}" @checked(in_array($condition, $selectedConditions, true))>{{ $condition }}</label>@endforeach</div></fieldset>
        <label class="text-sm text-slate-600 sm:col-span-2">Clinical notes<textarea name="notes" rows="3" class="{{ $input }} mt-1.5">{{ $value('notes') }}</textarea></label>
    </div>
</section>
