@extends('layouts.patient')
@section('page_title', 'My Profile')

@section('content')
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp
<div class="page-header">
    <div><h1 class="page-title">My Profile</h1><p class="page-subtitle">Manage contact details and sign-in security.</p></div>
</div>

<div class="grid min-w-0 gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
    <aside class="responsive-card responsive-card-padding lg:sticky lg:top-24">
        <div class="flex items-center gap-4 lg:flex-col lg:text-center">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-emerald-100 text-2xl font-bold text-emerald-700 ring-4 ring-emerald-50">{{ $initials ?: '?' }}</div>
            <div class="min-w-0">
                <h2 class="break-content text-lg font-bold text-slate-800">{{ $user->name }}</h2>
                <div class="mt-2 flex flex-wrap gap-2 lg:justify-center">
                    <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Patient</span>
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($user->status) }}</span>
                </div>
            </div>
        </div>
        <dl class="mt-5 space-y-4 border-t border-slate-100 pt-5 text-sm">
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Login email</dt><dd class="break-content mt-1 text-slate-700">{{ $user->email }}</dd></div>
            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Member since</dt><dd class="mt-1 text-slate-700">{{ $user->created_at->format('F Y') }}</dd></div>
        </dl>
    </aside>

    <div class="space-y-5">
        <section class="responsive-card">
            <div class="border-b border-slate-100 px-4 py-4 sm:px-6"><h2 class="font-bold text-slate-800">Personal Information</h2><p class="mt-1 text-sm text-slate-500">These details are used for appointment coordination.</p></div>
            <form action="{{ route('patient.profile.update') }}" method="POST" class="p-4 sm:p-6">
                @csrf @method('PATCH')
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach([
                        ['first_name','First name',$patient->first_name,'text'],
                        ['last_name','Last name',$patient->last_name,'text'],
                        ['mobile','Mobile number',$patient->mobile,'tel'],
                        ['dob','Date of birth',optional($patient->dob)->toDateString(),'date'],
                        ['gender','Gender',$patient->gender,'text'],
                        ['civil_status','Civil status',$patient->civil_status,'text'],
                        ['occupation','Occupation',$patient->occupation,'text'],
                        ['emergency_contact_name','Emergency contact name',$patient->emergency_contact_name,'text'],
                        ['emergency_contact_number','Emergency contact number',$patient->emergency_contact_number,'tel'],
                    ] as [$name, $label, $value, $type])
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                            @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">Address</label><textarea name="address" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">{{ old('address', $patient->address) }}</textarea></div>
                </div>
                <div class="mt-6 flex justify-end border-t border-slate-100 pt-5"><button class="primary-action w-full sm:w-auto"><i class="fa-solid fa-check"></i> Save profile</button></div>
            </form>
        </section>

        <section id="security" class="responsive-card">
            <div class="border-b border-slate-100 px-4 py-4 sm:px-6"><h2 class="font-bold text-slate-800">Security</h2><p class="mt-1 text-sm text-slate-500">Your login email is read-only in this release.</p></div>
            <form action="{{ route('patient.profile.security') }}" method="POST" class="p-4 sm:p-6" x-data="{ showCurrent: false, showPassword: false, showConfirm: false }">
                @csrf @method('PUT')
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">Login email</label><input value="{{ $user->email }}" disabled class="w-full rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm text-slate-500"></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label><div class="relative"><input name="current_password" :type="showCurrent ? 'text' : 'password'" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm"><button type="button" x-on:click="showCurrent = ! showCurrent" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid" :class="showCurrent ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">New password</label><div class="relative"><input name="password" :type="showPassword ? 'text' : 'password'" required minlength="8" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm"><button type="button" x-on:click="showPassword = ! showPassword" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label><div class="relative"><input name="password_confirmation" :type="showConfirm ? 'text' : 'password'" required minlength="8" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm"><button type="button" x-on:click="showConfirm = ! showConfirm" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                </div>
                <div class="mt-6 flex justify-end border-t border-slate-100 pt-5"><button class="primary-action w-full sm:w-auto"><i class="fa-solid fa-shield-halved"></i> Update password</button></div>
            </form>
        </section>
    </div>
</div>
@endsection
