@extends('layouts.app')
@section('page_title', 'My Profile')

@section('content')
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
    $roleColors = ['admin' => 'bg-violet-100 text-violet-700', 'dentist' => 'bg-blue-100 text-blue-700', 'receptionist' => 'bg-amber-100 text-amber-700'];
@endphp

<div class="mx-auto max-w-6xl">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Manage your personal information and sign-in security.</p>
        </div>
    </div>

    <div aria-live="polite" class="sr-only">{{ session('status') }}</div>

    <div class="grid min-w-0 gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
        <aside class="responsive-card responsive-card-padding lg:sticky lg:top-24">
            <div class="flex items-center gap-4 lg:flex-col lg:text-center">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-emerald-100 text-2xl font-bold text-emerald-700 ring-4 ring-emerald-50">{{ $initials ?: '?' }}</div>
                <div class="min-w-0">
                    <h2 class="break-content text-lg font-bold text-slate-800">{{ $user->name }}</h2>
                    <div class="mt-2 flex flex-wrap gap-2 lg:justify-center">
                        <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $roleColors[$user->role] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($user->role) }}</span>
                        <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($user->status) }}</span>
                    </div>
                </div>
            </div>
            <dl class="mt-5 space-y-4 border-t border-slate-100 pt-5 text-sm">
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Login email</dt><dd class="break-content mt-1 text-slate-700">{{ $user->email }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Member since</dt><dd class="mt-1 text-slate-700">{{ $user->created_at->format('F Y') }}</dd></div>
            </dl>
        </aside>

        <div class="min-w-0 space-y-5">
            <section id="personal-information" class="responsive-card scroll-mt-24">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-6">
                    <h2 class="font-bold text-slate-800">Personal Information</h2>
                    <p class="mt-1 text-sm text-slate-500">Keep your staff contact details current.</p>
                </div>
                <form action="{{ route('profile.details.update') }}" method="POST" data-ajax-form data-loading-text="Saving..." class="p-4 sm:p-6">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="profile-full-name" class="mb-1.5 block text-sm font-medium text-slate-700">Full name <span class="text-red-500">*</span></label>
                            <input id="profile-full-name" name="full_name" autocomplete="name" value="{{ old('full_name', $user->name) }}" aria-describedby="profile-name-help" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200" required maxlength="255">
                            <p id="profile-name-help" class="mt-1.5 text-xs text-slate-500">Use the name shown to patients and staff.</p>
                        </div>
                        <div class="{{ $user->role === 'dentist' ? '' : 'sm:col-span-2' }}">
                            <label for="profile-phone" class="mb-1.5 block text-sm font-medium text-slate-700">Phone number</label>
                            <input id="profile-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('phone', $user->phone) }}" placeholder="09XX-XXX-XXXX" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        </div>
                        @if ($user->role === 'dentist')
                            <div>
                                <label for="profile-license" class="mb-1.5 block text-sm font-medium text-slate-700">Professional license number</label>
                                <input id="profile-license" name="license_no" value="{{ old('license_no', $user->license_no) }}" placeholder="e.g. 0012345" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                            </div>
                        @endif
                    </div>
                    <div class="mt-6 flex justify-end border-t border-slate-100 pt-5"><button type="submit" class="primary-action w-full sm:w-auto"><i class="fa-solid fa-check" aria-hidden="true"></i>Save personal information</button></div>
                </form>
            </section>

            <section id="security" class="responsive-card scroll-mt-24 {{ $user->must_change_password ? 'border-amber-300 ring-4 ring-amber-100/70' : '' }}">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $user->must_change_password ? 'bg-amber-100 text-amber-700' : 'bg-emerald-50 text-emerald-600' }}"><i class="fa-solid fa-shield-halved"></i></span>
                        <div><h2 class="font-bold text-slate-800">Security</h2><p class="mt-1 text-sm text-slate-500">Change the email or password used to sign in.</p></div>
                    </div>
                    @if ($user->must_change_password)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"><strong>Temporary password in use.</strong> Create a private password before continuing to use your account.</div>
                    @endif
                </div>
                <form action="{{ route('profile.security.update') }}" method="POST" data-ajax-form data-loading-text="Updating..." class="p-4 sm:p-6" x-data="{ email: @js(old('email', $user->email)), originalEmail: @js($user->email), password: '', confirm: '', showCurrent: false, showPassword: false, showConfirm: false }">
                    @csrf
                    @method('PUT')
                    <div class="space-y-5">
                        <div>
                            <label for="profile-email" class="mb-1.5 block text-sm font-medium text-slate-700">Login email <span class="text-red-500">*</span></label>
                            <input id="profile-email" name="email" type="email" autocomplete="email" x-model="email" aria-describedby="profile-email-help" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200" required>
                            <p id="profile-email-help" class="mt-1.5 text-xs text-slate-500">Changing this address changes what you use for future sign-ins.</p>
                        </div>
                        <div>
                            <label for="profile-current-password" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                            <div class="relative"><input id="profile-current-password" name="current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password" placeholder="Required for security changes" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"><button type="button" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" x-on:click="showCurrent = !showCurrent" :aria-label="showCurrent ? 'Hide current password' : 'Show current password'"><i class="fa-solid" :class="showCurrent ? 'fa-eye-slash' : 'fa-eye'"></i></button></div>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="profile-new-password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                                <div class="relative"><input id="profile-new-password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" x-model="password" minlength="8" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"><button type="button" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" x-on:click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide new password' : 'Show new password'"><i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i></button></div>
                                <p class="mt-1.5 text-xs" :class="password.length >= 8 ? 'text-emerald-600' : 'text-slate-500'"><i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i>At least 8 characters</p>
                            </div>
                            <div>
                                <label for="profile-confirm-password" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
                                <div class="relative"><input id="profile-confirm-password" name="password_confirmation" :type="showConfirm ? 'text' : 'password'" autocomplete="new-password" x-model="confirm" minlength="8" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200"><button type="button" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" x-on:click="showConfirm = !showConfirm" :aria-label="showConfirm ? 'Hide password confirmation' : 'Show password confirmation'"><i class="fa-solid" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i></button></div>
                                <p x-show="confirm.length" class="mt-1.5 text-xs" :class="password === confirm ? 'text-emerald-600' : 'text-amber-600'" x-text="password === confirm ? 'Passwords match.' : 'Passwords do not match yet.'"></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end border-t border-slate-100 pt-5"><button type="submit" class="primary-action w-full disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto" :disabled="email === originalEmail && !password"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Update security</button></div>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if ($user->must_change_password)
<script>if (window.location.hash === '#security') requestAnimationFrame(() => document.getElementById('profile-current-password')?.focus());</script>
@endif
@endpush
