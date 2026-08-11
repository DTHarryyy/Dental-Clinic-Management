@extends('layouts.auth')
@section('page_title', 'Forgot Password')

@section('content')
    <div class="text-center mb-6">
        <div class="h-12 w-12 rounded-2xl bg-amber-50 flex items-center justify-center text-2xl mx-auto mb-3 text-amber-500"><i class="fa-solid fa-key"></i></div>
        <h1 class="text-2xl font-bold">Reset your password</h1>
        <p class="text-slate-500 text-sm mt-1">We'll send a reset link to your email address.</p>
    </div>

    @if(session('status'))<div class="mb-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
    <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="admin@dentalcare.com"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition"
            />
        </div>

        <button
            type="submit"
            class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition shadow-sm"
        >
            Send Reset Link
        </button>
    </form>

    <div class="mt-5 text-center">
        <a href="/login" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
            ← Back to sign in
        </a>
    </div>
@endsection
