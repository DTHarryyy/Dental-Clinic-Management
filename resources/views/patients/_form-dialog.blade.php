@php
    $isEdit = isset($patient) && $patient;
    $action = $isEdit ? route('patients.update', $patient) : route('patients.store');
    $val = fn ($field, $default = '') => old($field, $isEdit ? ($patient->{$field} ?? $default) : $default);

    // Shared field styles — keep inputs visually consistent and easy to tweak in one place.
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-500/10';
    $label = 'mb-1.5 block text-[13px] font-medium text-slate-600';
    $selectedConditions = old('conditions', $isEdit ? ($patient->conditions ?? []) : []);
@endphp

<form action="{{ $action }}" method="POST" data-ajax-form data-loading-text="{{ $isEdit ? 'Saving...' : 'Adding...' }}" class="flex min-h-0 flex-1 flex-col">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    {{-- Scrolling field region --}}
    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
    <div data-error-summary class="hidden"></div>
    <div class="divide-y divide-slate-100">
        {{-- Personal Information --}}
        <section class="pb-7">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-user text-sm"></i>
                </span>
                <div>
                    <h3 class="text-sm font-semibold leading-tight text-slate-800">Personal Information</h3>
                    <p class="mt-0.5 text-xs leading-tight text-slate-400">Basic details · fields marked <span class="font-semibold text-red-500">*</span> are required</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ $val('first_name') }}" placeholder="Maria" class="{{ $input }}" required />
                </div>
                <div>
                    <label class="{{ $label }}">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ $val('last_name') }}" placeholder="Santos" class="{{ $input }}" required />
                </div>
                <div>
                    <label class="{{ $label }}">Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob', $isEdit ? optional($patient->dob)->format('Y-m-d') : '') }}" class="{{ $input }}" />
                </div>
                <div>
                    <label class="{{ $label }}">Gender</label>
                    <select name="gender" class="{{ $input }}">
                        <option value="">Select gender</option>
                        @foreach (['Male', 'Female', 'Prefer not to say'] as $g)
                            <option {{ $val('gender') === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Civil Status</label>
                    <select name="civil_status" class="{{ $input }}">
                        <option value="">Select status</option>
                        @foreach (['Single', 'Married', 'Widowed', 'Divorced'] as $cs)
                            <option {{ $val('civil_status') === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Occupation</label>
                    <input type="text" name="occupation" value="{{ $val('occupation') }}" placeholder="e.g. Teacher" class="{{ $input }}" />
                </div>
            </div>
        </section>

        {{-- Contact Information --}}
        <section class="py-7">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <i class="fa-solid fa-address-book text-sm"></i>
                </span>
                <div>
                    <h3 class="text-sm font-semibold leading-tight text-slate-800">Contact Information</h3>
                    <p class="mt-0.5 text-xs leading-tight text-slate-400">How to reach the patient</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">Mobile Number</label>
                    <input type="tel" name="mobile" value="{{ $val('mobile') }}" placeholder="09XX-XXX-XXXX" class="{{ $input }}" />
                </div>
                <div>
                    <label class="{{ $label }}">Email Address</label>
                    <input type="email" name="email" value="{{ $val('email') }}" placeholder="email@example.com" class="{{ $input }}" />
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Home Address</label>
                    <textarea name="address" rows="2" placeholder="Street, Barangay, City, Province" class="{{ $input }} resize-none">{{ $val('address') }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="{{ $val('emergency_contact_name') }}" placeholder="Full name" class="{{ $input }}" />
                </div>
                <div>
                    <label class="{{ $label }}">Emergency Contact Number</label>
                    <input type="tel" name="emergency_contact_number" value="{{ $val('emergency_contact_number') }}" placeholder="09XX-XXX-XXXX" class="{{ $input }}" />
                </div>
            </div>
        </section>

        {{-- Medical History --}}
        <section class="pt-7">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-500">
                    <i class="fa-solid fa-notes-medical text-sm"></i>
                </span>
                <div>
                    <h3 class="text-sm font-semibold leading-tight text-slate-800">Medical History</h3>
                    <p class="mt-0.5 text-xs leading-tight text-slate-400">Allergies, medications &amp; conditions</p>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">Known Allergies</label>
                        <input type="text" name="allergies" value="{{ $val('allergies') }}" placeholder="e.g. Penicillin, Latex" class="{{ $input }}" />
                    </div>
                    <div>
                        <label class="{{ $label }}">Current Medications</label>
                        <input type="text" name="medications" value="{{ $val('medications') }}" placeholder="List any medications" class="{{ $input }}" />
                    </div>
                </div>
                <div data-field-group="conditions">
                    <label class="{{ $label }}">Medical Conditions</label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach (['Diabetes', 'Hypertension', 'Heart Disease', 'Asthma', 'Bleeding Disorder', 'None'] as $condition)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600 transition hover:border-slate-300 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-800">
                                <input type="checkbox" name="conditions[]" value="{{ $condition }}" {{ in_array($condition, $selectedConditions) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/20" />
                                <span class="font-medium">{{ $condition }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="{{ $label }}">Additional Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any other relevant medical information..." class="{{ $input }} resize-none">{{ $val('notes') }}</textarea>
                </div>
            </div>
        </section>
    </div>
    </div>

    {{-- Pinned action bar — flush to the bottom of the dialog, no gap --}}
    <div class="responsive-action-bar shrink-0 shadow-[0_-10px_24px_-16px_rgba(15,23,42,0.18)]">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-600">
            <span>Status</span>
            <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                <option value="active" {{ $val('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $val('status', 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </label>
        <div class="flex items-center gap-3">
            <button type="button" x-on:click="open = false" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
                <i class="fa-solid fa-check text-xs"></i>
                {{ $isEdit ? 'Save Changes' : 'Save Patient' }}
            </button>
        </div>
    </div>
</form>
