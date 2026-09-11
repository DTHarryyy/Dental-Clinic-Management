@extends('layouts.auth')
@section('page_title', 'Confirm Verification')

@section('content')
<div class="text-center">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600">
        <i class="fa-solid fa-shield-heart"></i>
    </div>
    <h1 class="mt-4 text-2xl font-bold text-slate-800">Confirm your email link</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">This fallback screen supports older verification emails that contain a secure link.</p>
</div>

@if ($tokenHash)
    <form action="{{ route('verify-email.consume') }}" method="POST" class="mt-6">
        @csrf
        <input type="hidden" name="token_hash" value="{{ $tokenHash }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
            <i class="fa-solid fa-circle-check"></i> Confirm email
        </button>
    </form>
@else
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">This verification link is missing its token. Please request a new code.</div>
    <a href="{{ route('verify-email') }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Enter or request a code</a>
@endif
@endsection
