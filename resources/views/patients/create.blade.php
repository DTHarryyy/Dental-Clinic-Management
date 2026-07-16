@extends('layouts.app')
@section('page_title', 'Add Patient')

@section('content')
{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
    <a href="{{ route('patients.index') }}" class="hover:text-emerald-600 transition">Patients</a>
    <span>/</span>
    <span class="text-slate-800 font-medium">Add New Patient</span>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Add New Patient</h1>
    <a href="{{ route('patients.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back to Patients</a>
</div>

<form action="{{ route('patients.store') }}" method="POST">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Main form --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Personal Information --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Personal Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Maria" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Santos" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
                        <input type="date" name="dob" value="{{ old('dob') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">Select gender</option>
                            <option {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                            <option {{ old('gender') === 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Civil Status</label>
                        <select name="civil_status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                            <option value="">Select status</option>
                            <option>Single</option>
                            <option>Married</option>
                            <option>Widowed</option>
                            <option>Divorced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Occupation</label>
                        <input type="text" name="occupation" value="{{ old('occupation') }}" placeholder="e.g. Teacher" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Contact Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Mobile Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Home Address</label>
                        <textarea name="address" rows="2" placeholder="Street, Barangay, City, Province" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" placeholder="Full name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Number</label>
                        <input type="tel" name="emergency_contact_number" value="{{ old('emergency_contact_number') }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
            </div>

            {{-- Medical History --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-semibold text-base text-slate-800 mb-5 pb-4 border-b border-slate-100">Medical History</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Known Allergies</label>
                        <input type="text" name="allergies" value="{{ old('allergies') }}" placeholder="e.g. Penicillin, Latex (leave blank if none)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Current Medications</label>
                        <input type="text" name="medications" value="{{ old('medications') }}" placeholder="List any medications" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-3">Medical Conditions</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach (['Diabetes', 'Hypertension', 'Heart Disease', 'Asthma', 'Bleeding Disorder', 'None'] as $condition)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="conditions[]" value="{{ $condition }}" {{ in_array($condition, old('conditions', [])) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                                    <span class="text-sm text-slate-700">{{ $condition }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Additional Notes</label>
                        <textarea name="notes" rows="3" placeholder="Any other relevant medical information..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar summary --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-sm text-slate-800 mb-4">Status</h3>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                    <p class="text-xs text-amber-700">Fields marked with <span class="text-red-500 font-bold">*</span> are required. All patient data is stored securely.</p>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('patients.index') }}">
                    <button type="button" class="w-full py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </a>
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    Save Patient
                </button>
            </div>
        </div>

    </div>
</form>
@endsection
