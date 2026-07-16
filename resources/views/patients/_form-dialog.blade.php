@php
    $isEdit = isset($patient) && $patient;
    $action = $isEdit ? route('patients.update', $patient) : route('patients.store');
    $val = fn ($field, $default = '') => old($field, $isEdit ? ($patient->{$field} ?? $default) : $default);
@endphp

<form action="{{ $action }}" method="POST" data-ajax-form data-loading-text="{{ $isEdit ? 'Saving...' : 'Adding...' }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="space-y-6">
        {{-- Personal Information --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Personal Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ $val('first_name') }}" placeholder="Maria" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ $val('last_name') }}" placeholder="Santos" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob', $isEdit ? optional($patient->dob)->format('Y-m-d') : '') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender</label>
                    <select name="gender" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                        <option value="">Select gender</option>
                        @foreach (['Male', 'Female', 'Prefer not to say'] as $g)
                            <option {{ $val('gender') === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Civil Status</label>
                    <select name="civil_status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                        <option value="">Select status</option>
                        @foreach (['Single', 'Married', 'Widowed', 'Divorced'] as $cs)
                            <option {{ $val('civil_status') === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Occupation</label>
                    <input type="text" name="occupation" value="{{ $val('occupation') }}" placeholder="e.g. Teacher" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Contact Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Mobile Number</label>
                    <input type="tel" name="mobile" value="{{ $val('mobile') }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ $val('email') }}" placeholder="email@example.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Home Address</label>
                    <textarea name="address" rows="2" placeholder="Street, Barangay, City, Province" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ $val('address') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="{{ $val('emergency_contact_name') }}" placeholder="Full name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Number</label>
                    <input type="tel" name="emergency_contact_number" value="{{ $val('emergency_contact_number') }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
            </div>
        </div>

        {{-- Medical History --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Medical History</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Known Allergies</label>
                    <input type="text" name="allergies" value="{{ $val('allergies') }}" placeholder="e.g. Penicillin, Latex (leave blank if none)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Current Medications</label>
                    <input type="text" name="medications" value="{{ $val('medications') }}" placeholder="List any medications" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div data-field-group="conditions">
                    <label class="block text-sm font-medium text-slate-700 mb-3">Medical Conditions</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @php $selectedConditions = old('conditions', $isEdit ? ($patient->conditions ?? []) : []); @endphp
                        @foreach (['Diabetes', 'Hypertension', 'Heart Disease', 'Asthma', 'Bleeding Disorder', 'None'] as $condition)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="conditions[]" value="{{ $condition }}" {{ in_array($condition, $selectedConditions) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                                <span class="text-sm text-slate-700">{{ $condition }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Additional Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any other relevant medical information..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ $val('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-4 bg-slate-50 rounded-xl p-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="active" {{ $val('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $val('status', 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <p class="text-xs text-slate-400 max-w-xs text-right">Fields marked with <span class="text-red-500 font-bold">*</span> are required. All patient data is stored securely.</p>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-slate-100">
        <button type="button" x-on:click="open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
            Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
            {{ $isEdit ? 'Save Changes' : 'Save Patient' }}
        </button>
    </div>
</form>
