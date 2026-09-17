@extends('layouts.auth')
@section('page_title', 'Create Patient Account')

@section('content')
@php $err = fn ($field) => $errors->has($field) ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200'; @endphp

<h1 class="text-center text-2xl font-bold text-slate-800">Create patient account</h1>
<p class="mb-6 mt-1 text-center text-sm text-slate-500">Register once, enter the email code, then book appointments securely.</p>

@if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <i class="fa-solid fa-circle-exclamation mr-1.5"></i> Please fix the highlighted fields below.
    </div>
@endif

<form action="{{ route('register.store') }}" method="POST" class="space-y-5" x-data="{ showPassword: false, showConfirm: false, submitting: false }" x-on:submit="submitting = true">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">First name <span class="text-red-500">*</span></label>
            <input name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required class="w-full rounded-xl border {{ $err('first_name') }} bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
            @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Last name <span class="text-red-500">*</span></label>
            <input name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required class="w-full rounded-xl border {{ $err('last_name') }} bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
            @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Mobile number <span class="text-red-500">*</span></label>
        <input name="mobile" value="{{ old('mobile') }}" type="tel" inputmode="tel" autocomplete="tel" required class="w-full rounded-xl border {{ $err('mobile') }} bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        @error('mobile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Email address <span class="text-red-500">*</span></label>
        <input name="email" value="{{ old('email') }}" type="email" autocomplete="email" required class="w-full rounded-xl border {{ $err('email') }} bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Password <span class="text-red-500">*</span></label>
            <div class="relative">
                <input name="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required minlength="8" class="w-full rounded-xl border {{ $err('password') }} bg-slate-50 px-4 py-2.5 pr-12 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <button type="button" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400" x-on:click="showPassword = ! showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'"><i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i></button>
            </div>
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password <span class="text-red-500">*</span></label>
            <div class="relative">
                <input name="password_confirmation" :type="showConfirm ? 'text' : 'password'" autocomplete="new-password" required minlength="8" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-12 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <button type="button" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400" x-on:click="showConfirm = ! showConfirm" :aria-label="showConfirm ? 'Hide password confirmation' : 'Show password confirmation'"><i class="fa-solid" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i></button>
            </div>
        </div>
    </div>

    <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <label class="flex items-start gap-3 text-xs text-slate-600">
            <input type="checkbox" name="privacy_accepted" value="1" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-200">
            <span>I accept the <a href="{{ route('privacy') }}" class="font-semibold text-emerald-700 hover:underline" target="_blank">privacy policy</a>.</span>
        </label>
        <label class="flex items-start gap-3 text-xs text-slate-600">
            <input type="checkbox" name="terms_accepted" value="1" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-200">
            <span>I accept the <a href="{{ route('terms') }}" class="font-semibold text-emerald-700 hover:underline" target="_blank">terms of use</a>.</span>
        </label>
    </div>

    <button type="submit" :disabled="submitting" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-70">
        <svg x-show="submitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0A12 12 0 0 0 0 12h4Z"></path>
        </svg>
        <i x-show="!submitting" class="fa-solid fa-user-check"></i>
        <span x-text="submitting ? 'Creating account…' : 'Create account'">Create account</span>
    </button>
</form>

<p class="mt-5 text-center text-sm text-slate-500">Already registered? <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:underline">Sign in</a></p>
@endsection
