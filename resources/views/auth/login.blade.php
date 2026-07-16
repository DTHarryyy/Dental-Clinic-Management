@extends('layouts.auth')
@section('page_title', 'Sign In')

@section('content')
    <h1 class="text-2xl font-bold text-center">Welcome back</h1>
    <p class="text-slate-500 text-sm text-center mt-1 mb-6">Sign in to your admin account</p>

    <form action="{{ route('login.attempt') }}" method="POST" class="space-y-5">
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
                <a href="/forgot-password" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">
                    Forgot password?
                </a>
            </div>
            <input
                type="password"
                name="password"
                placeholder="Enter password"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition"
                required
            />
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" id="remember" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
            <label for="remember" class="text-sm text-slate-600">Remember me for 30 days</label>
        </div>

        <button
            type="submit"
            class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition shadow-sm"
        >
            Sign In
        </button>
    </form>

@endsection
