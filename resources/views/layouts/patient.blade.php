<!DOCTYPE html>
<html lang="en" class="app-html">
<head>
    <title>@yield('page_title', 'Patient Portal') - DentalCare</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-enabled" content="{{ config('performance.turbo_enabled') ? 'true' : 'false' }}">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">
    <link rel="icon" type="image/png" href="{{ asset('images/aquilizan-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>html, body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; } [x-cloak] { display: none !important; }</style>
    @stack('styles')
</head>
<body class="app-body bg-slate-50 text-slate-900 overflow-x-hidden" data-turbo-prefetch="true" x-data="{ mobileMenu: false }" x-on:keydown.escape.window="mobileMenu = false">
@include('components.toast')
@php
    $user = auth()->user();
    $unreadNotificationCount = $user->unreadNotifications()->count();
    $patientItems = [
        ['route' => 'patient.dashboard', 'label' => 'Dashboard', 'short' => 'Home', 'icon' => 'fa-gauge', 'primary' => true],
        ['route' => 'patient.appointments.index', 'label' => 'Appointments', 'short' => 'Visits', 'icon' => 'fa-calendar-days', 'primary' => true],
        ['route' => 'patient.billing.index', 'label' => 'Billing', 'short' => 'Billing', 'icon' => 'fa-credit-card', 'primary' => true],
        ['route' => 'patient.treatments.index', 'label' => 'Treatments', 'short' => 'Care', 'icon' => 'fa-notes-medical', 'primary' => true],
        ['route' => 'patient.notifications.index', 'label' => 'Notifications', 'short' => 'Alerts', 'icon' => 'fa-bell', 'primary' => false],
        ['route' => 'patient.profile', 'label' => 'Profile', 'short' => 'Profile', 'icon' => 'fa-user', 'primary' => false],
    ];
@endphp
<div class="app-shell min-h-screen flex">
    <aside class="hidden lg:flex h-full min-h-0 w-64 shrink-0 flex-col overflow-hidden border-r border-slate-200 bg-white print:hidden" data-app-sidebar>
        <div class="px-5 py-4 border-b border-slate-200">
            <a href="{{ route('patient.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-10 w-10 rounded-2xl object-contain shadow-sm" />
                <div><div class="font-bold leading-tight text-slate-800">DentalCare</div><div class="text-xs text-slate-500">Patient Portal</div></div>
            </a>
        </div>
        <nav class="min-h-0 flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
            <p class="px-3 pt-1 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Patient</p>
            @foreach($patientItems as $item)
                @php $active = request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" class="app-nav-item {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                    <span class="app-nav-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span><span class="font-medium text-sm">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="border-t border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700 text-sm shrink-0">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                <div class="leading-tight min-w-0"><div class="font-semibold text-sm truncate">{{ $user->name }}</div><div class="text-xs text-slate-500">Patient</div></div>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'logout-confirm' } }))" class="touch-target ml-auto rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Log out"><i class="fa-solid fa-right-from-bracket"></i></button>
            </div>
        </div>
    </aside>

    <div class="app-content min-w-0 min-h-0 flex-1 flex flex-col" data-app-scroll>
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white print:hidden">
            <div class="flex items-center justify-between gap-3 px-3 py-2.5 sm:px-5 sm:py-3.5 lg:px-8">
                <button type="button" class="touch-target rounded-xl border border-slate-200 text-slate-600 lg:hidden" x-on:click="mobileMenu = true" aria-label="Open navigation"><i class="fa-solid fa-bars"></i></button>
                <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-widest text-emerald-600">Patient Portal</p><p class="truncate text-sm font-semibold text-slate-700">{{ $user->patient?->name ?? $user->name }}</p></div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('patient.notifications.index') }}" class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        @if($unreadNotificationCount)<span class="absolute -right-1.5 -top-1.5 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ min($unreadNotificationCount, 99) }}</span>@endif
                    </a>
                    <a href="{{ route('patient.profile') }}" class="hidden rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:inline-flex"><i class="fa-solid fa-user mr-1.5"></i> Profile</a>
                </div>
            </div>
        </header>
        <main class="app-main print:p-0"><div class="app-container">@yield('content')</div></main>
    </div>
</div>

<div x-show="mobileMenu" x-cloak class="fixed inset-0 z-40 lg:hidden print:hidden" role="dialog" aria-modal="true" aria-label="Navigation menu">
    <button type="button" class="absolute inset-0 bg-slate-950/45" x-on:click="mobileMenu = false" aria-label="Close navigation"></button>
    <aside x-show="mobileMenu" x-transition class="absolute inset-y-0 left-0 flex w-[min(20rem,88vw)] flex-col bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <a href="{{ route('patient.dashboard') }}" class="flex items-center gap-3 font-bold text-slate-800"><img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-10 w-10 rounded-2xl object-contain" />DentalCare</a>
            <button type="button" class="touch-target rounded-xl text-slate-500" x-on:click="mobileMenu = false" aria-label="Close navigation"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto p-4" x-on:click="if ($event.target.closest('a')) mobileMenu = false">
            @foreach($patientItems as $item)
                @php $active = request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" class="app-nav-item {{ $active ? 'is-active' : '' }}"><span class="app-nav-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span><span class="text-sm font-medium">{{ $item['label'] }}</span></a>
            @endforeach
        </nav>
    </aside>
</div>

<nav class="mobile-bottom-nav sm:hidden print:hidden" aria-label="Patient navigation">
    @foreach(collect($patientItems)->where('primary', true)->take(4) as $item)
        @php $active = request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']); @endphp
        <a href="{{ route($item['route']) }}" class="mobile-nav-item {{ $active ? 'is-active' : '' }}"><i class="fa-solid {{ $item['icon'] }}"></i><span>{{ $item['short'] }}</span></a>
    @endforeach
    <button type="button" class="mobile-nav-item" x-on:click="mobileMenu = true"><i class="fa-solid fa-ellipsis"></i><span>More</span></button>
</nav>

<x-modal name="logout-confirm" title="Confirm logout" max-width="md">
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-xl text-red-600"><i class="fa-solid fa-right-from-bracket"></i></div>
        <h3 class="mt-4 font-bold text-slate-800">Log out of DentalCare?</h3>
        <p class="mt-2 text-sm text-slate-500">You will need to sign in again to access your patient portal.</p>
    </div>
    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <button type="button" x-on:click="open = false" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">Stay signed in</button>
        <form action="{{ route('logout') }}" method="POST" data-turbo="false" class="w-full sm:w-auto">@csrf<button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 text-sm font-semibold text-white hover:bg-red-700"><i class="fa-solid fa-right-from-bracket"></i>Log out</button></form>
    </div>
</x-modal>
@stack('scripts')
</body>
</html>
