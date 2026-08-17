<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('page_title', 'DentalCare') — Login</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/aquilizan-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        html, body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-[100dvh] flex items-center justify-center p-3 sm:p-4">
    @include('components.toast')

    <div class="w-full max-w-md">
        {{-- Brand --}}
        <div class="flex flex-col items-center mb-5 sm:mb-8">
            <div class="h-14 w-14 rounded-2xl overflow-hidden shadow-lg">
                <img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-full w-full object-contain" />
            </div>
            <div class="mt-3 text-center">
                <div class="text-xl font-bold">DentalCare</div>
                <div class="text-sm text-slate-500">Management System</div>
            </div>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl border shadow-sm p-5 sm:p-8">
            @yield('content')
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-slate-400 mt-6">
            &copy; {{ date('Y') }} DentalCare. All rights reserved.
        </p>
    </div>

</body>
</html>
