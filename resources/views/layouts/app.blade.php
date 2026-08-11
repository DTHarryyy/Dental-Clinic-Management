<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('page_title', 'DentalCare')</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-enabled" content="{{ config('performance.turbo_enabled') ? 'true' : 'false' }}">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('js/dialog-forms.js') }}" defer></script>
    <script src="{{ asset('js/auto-filter.js') }}" defer></script>

    <style>
        html, body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>

<body class="bg-slate-50 text-slate-900" data-turbo-prefetch="true">
@include('components.toast')
<div class="min-h-screen flex">

    {{-- Sidebar --}}
    @include('components.sidebar')

    {{-- Main area --}}
    <div class="flex-1 flex flex-col">
        {{-- Topbar --}}
        @include('components.topbar')

        {{-- Page content --}}
        <main class="px-6 lg:px-8 py-6 print:p-0">
            @if(auth()->user()->must_change_password)
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'profile-edit' } }))" class="w-full mb-5 text-left rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <strong>Security reminder:</strong> You are using a temporary password. Open your profile and replace it now.
                </button>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<x-modal name="profile-edit" title="My Profile" max-width="3xl">
    @include('profile._form-dialog')
</x-modal>

@stack('scripts')
</body>
</html>
