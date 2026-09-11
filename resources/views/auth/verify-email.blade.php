@extends('layouts.auth')
@section('page_title', 'Verify Email')

@section('content')
@php $prefillEmail = $email ?? ''; @endphp

<div class="text-center">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600">
        <i class="fa-solid fa-envelope-circle-check"></i>
    </div>
    <h1 class="mt-4 text-2xl font-bold text-slate-800">Enter verification code</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">We sent a 6-digit code to your email. Enter it here to activate your patient account.</p>
</div>

@if ($errors->any())
    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <i class="fa-solid fa-circle-exclamation mr-1.5"></i> Please check the email address and code below.
    </div>
@endif

<form action="{{ route('verify-email.consume') }}" method="POST" class="mt-6 space-y-4">
    @csrf
    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
        <input type="email" name="email" value="{{ old('email', $prefillEmail) }}" autocomplete="email" required class="w-full rounded-xl border {{ $errors->has('email') ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200' }} bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">6-digit code</label>
        <input type="text" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required class="w-full rounded-xl border {{ $errors->has('code') ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200' }} bg-slate-50 px-4 py-3 text-center text-lg font-bold tracking-[0.35em] text-slate-800 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <p class="mt-1 text-xs text-slate-500">Codes usually expire within Supabase’s configured email OTP window.</p>
    </div>
    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
        <i class="fa-solid fa-circle-check"></i> Verify email
    </button>
</form>

<form action="{{ route('verify-email.resend') }}" method="POST" class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
    @csrf
    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Need another code?</label>
    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
        <input type="email" name="email" value="{{ old('email', $prefillEmail) }}" placeholder="patient@example.com" required class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200">
        <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
        <i class="fa-solid fa-paper-plane"></i> Send a new code
        </button>
    </div>
</form>

<p class="mt-5 text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:underline">Back to sign in</a></p>
@endsection
