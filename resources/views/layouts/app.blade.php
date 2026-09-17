<!DOCTYPE html>
{{-- app-html scopes the desktop scroll-lock in app.css to this shell, not standalone public pages --}}
<html lang="en" class="app-html">
<head>
    <title>@yield('page_title', 'DentalCare')</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-enabled" content="{{ config('performance.turbo_enabled') ? 'true' : 'false' }}">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">

    <link rel="icon" type="image/png" href="{{ asset('images/aquilizan-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html, body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>

<body class="app-body bg-slate-50 text-slate-900 overflow-x-hidden @yield('body_class')" data-turbo-prefetch="true" x-data="{ mobileMenu: false }" x-on:keydown.escape.window="mobileMenu = false">
@include('components.toast')
<div class="app-shell min-h-screen flex">

    {{-- Sidebar --}}
    @include('components.sidebar')

    {{-- Main area --}}
    <div class="app-content min-w-0 min-h-0 flex-1 flex flex-col" data-app-scroll>
        {{-- Topbar --}}
        @include('components.topbar')

        {{-- Page content --}}
        <main class="app-main print:p-0">
            <div class="app-container">
            @if(auth()->user()->must_change_password)
                <a href="{{ route('profile.show') }}#security" class="block w-full mb-5 text-left rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <strong>Security reminder:</strong> You are using a temporary password. Open your profile and replace it now.
                </a>
            @endif
            @yield('content')
            </div>
        </main>
    </div>
</div>

<div x-show="mobileMenu" x-cloak class="fixed inset-0 z-40 lg:hidden print:hidden" role="dialog" aria-modal="true" aria-label="Navigation menu">
    <button type="button" class="absolute inset-0 bg-slate-950/45" x-on:click="mobileMenu = false" aria-label="Close navigation"></button>
    <aside x-show="mobileMenu" x-transition class="absolute inset-y-0 left-0 flex w-[min(20rem,88vw)] flex-col bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 font-bold text-slate-800"><span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl"><img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-full w-full object-contain" /></span>DentalCare</a>
            <button type="button" class="touch-target rounded-xl text-slate-500" x-on:click="mobileMenu = false" aria-label="Close navigation"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto p-4" x-on:click="if ($event.target.closest('a')) mobileMenu = false"><x-navigation variant="drawer" /></nav>
        <div class="space-y-2 border-t border-slate-200 p-4">
            <a href="{{ route('public.book') }}" target="_blank" class="app-nav-item"><span class="app-nav-icon"><i class="fa-solid fa-clipboard-list"></i></span><span class="text-sm font-medium">Patient Booking</span></a>
            <a href="{{ route('profile.show') }}" class="app-nav-item w-full"><span class="app-nav-icon"><i class="fa-solid fa-user"></i></span><span class="text-sm font-medium">My Profile</span></a>
            <button type="button" x-on:click="mobileMenu = false; $nextTick(() => window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'logout-confirm' } })))" class="app-nav-item w-full text-red-600"><span class="app-nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span><span class="text-sm font-medium">Logout</span></button>
        </div>
    </aside>
</div>

<nav class="mobile-bottom-nav sm:hidden print:hidden" aria-label="Primary navigation">
    <x-navigation variant="bottom" />
    <button type="button" class="mobile-nav-item" x-on:click="mobileMenu = true"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i><span>More</span></button>
</nav>

<x-modal name="logout-confirm" title="Confirm logout" max-width="md">
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-xl text-red-600"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></div>
        <h3 class="mt-4 font-bold text-slate-800">Log out of DentalCare?</h3>
        <p class="mt-2 text-sm text-slate-500">You’ll need to enter your credentials to access the clinic system again.</p>
    </div>
    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <button type="button" x-on:click="open = false" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Stay signed in</button>
        <form action="{{ route('logout') }}" method="POST" data-turbo="false" class="w-full sm:w-auto">
            @csrf
            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 text-sm font-semibold text-white transition hover:bg-red-700"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>Log out</button>
        </form>
    </div>
</x-modal>

@can('settings.view')
{{-- Two-pane settings dialog: a persistent rail on the left, and a right pane that is
     the ONLY thing tab switches replace. The split is flexbox, not `xl:` breakpoints,
     because Tailwind breakpoints key off the viewport and would collapse this layout
     inside a max-w-5xl panel on anything under 1280px. --}}
<x-modal name="settings-popover" hide-header max-width="5xl"
         panel-class="settings-popover-surface settings-dialog-panel"
         body-class="flex min-h-0 flex-1 p-0">
    <div class="settings-dialog" data-settings-dialog>
        <aside class="settings-dialog-rail">
            <h2 id="dialog-title-settings-popover" class="settings-dialog-title">Settings</h2>
            @include('settings._nav')
        </aside>
        <div class="settings-dialog-content">
            <div class="settings-dialog-toolbar">
                <button type="button" x-on:click="open = false" class="settings-dialog-close" aria-label="Close settings">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div data-settings-popover-body class="settings-dialog-scroll"></div>
        </div>
    </div>
</x-modal>
@endcan

@stack('scripts')
</body>
</html>
