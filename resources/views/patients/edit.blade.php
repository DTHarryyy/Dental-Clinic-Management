@extends('layouts.app')
@section('page_title', 'Edit Patient')

@section('content')

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('patients.index') }}" class="hover:text-emerald-600 transition">Patients</a>
    <span>/</span>
    <a href="{{ route('patients.show', $patient) }}" class="hover:text-emerald-600 transition">{{ $patient->name }}</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Edit</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Edit Patient</h1>
    <a href="{{ route('patients.show', $patient) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back to Profile</a>
</div>

<form action="{{ route('patients.update', $patient) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Personal Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $patient->first_name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $patient->last_name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
                        <input type="date" name="dob" value="{{ old('dob', optional($patient->dob)->format('Y-m-d')) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option {{ $patient->gender === 'Male' ? 'selected' : '' }}>Male</option>
                            <option {{ $patient->gender === 'Female' ? 'selected' : '' }}>Female</option>
                            <option {{ $patient->gender === 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Civil Status</label>
                        <select name="civil_status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option {{ $patient->civil_status === 'Single' ? 'selected' : '' }}>Single</option>
                            <option {{ $patient->civil_status === 'Married' ? 'selected' : '' }}>Married</option>
                            <option {{ $patient->civil_status === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                            <option {{ $patient->civil_status === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Occupation</label>
                        <input type="text" name="occupation" value="{{ old('occupation', $patient->occupation) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Contact Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Mobile Number</label>
                        <input type="tel" name="mobile" value="{{ old('mobile', $patient->mobile) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email', $patient->email) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Home Address</label>
                        <textarea name="address" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('address', $patient->address) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Number</label>
                        <input type="tel" name="emergency_contact_number" value="{{ old('emergency_contact_number', $patient->emergency_contact_number) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Medical History</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Known Allergies</label>
                        <input type="text" name="allergies" value="{{ old('allergies', $patient->allergies) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Current Medications</label>
                        <input type="text" name="medications" value="{{ old('medications', $patient->medications) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-3">Medical Conditions</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach (['Diabetes', 'Hypertension', 'Heart Disease', 'Asthma', 'Bleeding Disorder', 'None'] as $condition)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="conditions[]" value="{{ $condition }}" {{ in_array($condition, old('conditions', $patient->conditions ?? [])) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                                    <span class="text-sm text-slate-700">{{ $condition }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-3">Status</h3>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="active" {{ $patient->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $patient->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="bg-red-50 border border-red-100 rounded-2xl p-4">
                <h3 class="font-semibold text-sm text-red-700 mb-2">Danger Zone</h3>
                <p class="text-xs text-red-600 mb-3">Deactivating a patient hides them from active lists but preserves their data.</p>
                <form action="{{ route('patients.deactivate', $patient) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 rounded-xl border border-red-200 text-red-600 font-semibold text-sm hover:bg-red-100 transition">
                        {{ $patient->status === 'active' ? 'Deactivate Patient' : 'Reactivate Patient' }}
                    </button>
                </form>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('patients.show', $patient) }}">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
