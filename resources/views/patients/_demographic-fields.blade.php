@php
    $value = fn (string $field, mixed $default = '') => old($field, $patient?->{$field} ?? $default);
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-500/10';
    $showExtended = $showExtended ?? true;
@endphp
<section>
    <h3 class="mb-4 text-sm font-semibold text-slate-800">Demographics &amp; Contact</h3>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label class="text-sm text-slate-600">First name <span class="text-red-500">*</span><input name="first_name" value="{{ $value('first_name') }}" class="{{ $input }} mt-1.5" required></label>
        <label class="text-sm text-slate-600">Last name <span class="text-red-500">*</span><input name="last_name" value="{{ $value('last_name') }}" class="{{ $input }} mt-1.5" required></label>
        <label class="text-sm text-slate-600">Date of birth<input type="date" name="dob" value="{{ old('dob', $patient?->dob?->format('Y-m-d')) }}" class="{{ $input }} mt-1.5"></label>
        <label class="text-sm text-slate-600">Gender<select name="gender" class="{{ $input }} mt-1.5"><option value="">Select</option>@foreach(['Male','Female','Prefer not to say'] as $option)<option @selected($value('gender') === $option)>{{ $option }}</option>@endforeach</select></label>
        @if ($showExtended)
            <label class="text-sm text-slate-600">Civil status<select name="civil_status" class="{{ $input }} mt-1.5"><option value="">Select</option>@foreach(['Single','Married','Widowed','Divorced'] as $option)<option @selected($value('civil_status') === $option)>{{ $option }}</option>@endforeach</select></label>
            <label class="text-sm text-slate-600">Occupation<input name="occupation" value="{{ $value('occupation') }}" class="{{ $input }} mt-1.5"></label>
        @endif
        <label class="text-sm text-slate-600">Mobile<input name="mobile" value="{{ $value('mobile') }}" class="{{ $input }} mt-1.5"></label>
        <label class="text-sm text-slate-600">Email<input type="email" name="email" value="{{ $value('email') }}" class="{{ $input }} mt-1.5"></label>
        <label class="text-sm text-slate-600 sm:col-span-2">Address<textarea name="address" rows="2" class="{{ $input }} mt-1.5">{{ $value('address') }}</textarea></label>
        @if ($showExtended)
            <label class="text-sm text-slate-600">Emergency contact<input name="emergency_contact_name" value="{{ $value('emergency_contact_name') }}" class="{{ $input }} mt-1.5"></label>
            <label class="text-sm text-slate-600">Emergency number<input name="emergency_contact_number" value="{{ $value('emergency_contact_number') }}" class="{{ $input }} mt-1.5"></label>
        @endif
    </div>
</section>
