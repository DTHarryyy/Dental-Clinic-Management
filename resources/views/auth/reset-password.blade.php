@extends('layouts.auth')
@section('page_title', 'Reset Password')

@section('content')
<div class="text-center mb-6">
    <h1 class="text-2xl font-bold">Choose a new password</h1>
    <p class="text-slate-500 text-sm mt-1">The reset link can be used once and expires after 60 minutes.</p>
</div>
<form action="{{ route('password.update') }}" method="POST" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" value="{{ old('email', $email) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200"></div>
    <div><label class="block text-sm font-medium mb-1">New password</label><input type="password" name="password" required minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200"></div>
    <div><label class="block text-sm font-medium mb-1">Confirm password</label><input type="password" name="password_confirmation" required minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200"></div>
    @if($errors->any())<div class="text-sm text-red-600">{{ $errors->first() }}</div>@endif
    <button class="w-full py-3 rounded-xl bg-emerald-500 text-white font-semibold">Reset Password</button>
</form>
@endsection
