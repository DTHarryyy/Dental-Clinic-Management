@extends('layouts.auth')
@section('page_title', 'Sign In')

@section('content')
    <h1 class="text-2xl font-bold text-center text-slate-800">Sign in to DentalCare</h1>
    <p class="text-slate-500 text-sm text-center mt-1 mb-6">Use one secure account for clinic staff or patient access.</p>

    <form action="{{ route('login.attempt') }}" method="POST" class="space-y-5" x-data="{ showPassword: false }">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Enter email"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition"
                required
            />
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-sm font-medium text-slate-700">Password</label>
                <a href="{{ route('forgot-password') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">
                    Forgot password?
                </a>
            </div>
            <div class="relative">
                <input
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    placeholder="Enter password"
                    class="w-full px-4 py-2.5 pr-12 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition"
                    required
                />
                <button type="button" class="touch-target absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" x-on:click="showPassword = ! showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                    <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" id="remember" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
            <label for="remember" class="text-sm text-slate-600">Remember me for 30 days</label>
        </div>

        <button
            type="submit"
            class="w-full min-h-11 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition shadow-sm"
        >
            <i class="fa-solid fa-right-to-bracket mr-1.5"></i> Sign in
        </button>
    </form>

    <div class="mt-6 border-t border-slate-100 pt-5 text-center">
        <p class="text-sm text-slate-500">Need to book as a patient?</p>
        <a href="{{ route('register') }}" class="mt-3 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
            <i class="fa-solid fa-user-plus"></i> Create patient account
        </a>
    </div>

@endsection
