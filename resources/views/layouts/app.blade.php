<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('page_title', 'DentalCare')</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        html, body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    </style>
</head>

<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen flex">

    {{-- Sidebar --}}
    @include('components.sidebar')

    {{-- Main area --}}
    <div class="flex-1 flex flex-col">
        {{-- Topbar --}}
        @include('components.topbar')

        {{-- Page content --}}
        <main class="px-6 lg:px-8 py-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>