<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('page_title', $site->meta_title ?? 'Aquilizan Dental Clinic')</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="@yield('meta_description', $site->meta_description ?? 'Aquilizan Dental Clinic patient portal and appointment booking.')">
    <meta property="og:title" content="@yield('page_title', $site->meta_title ?? 'Aquilizan Dental Clinic')">
    <meta property="og:description" content="@yield('meta_description', $site->meta_description ?? 'Aquilizan Dental Clinic patient portal and appointment booking.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($site->og_image_path)<meta property="og:image" content="{{ asset('storage/'.$site->og_image_path) }}">@endif
    <link rel="icon" type="image/png" href="{{ asset('images/aquilizan-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>html, body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }</style>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    @include('components.toast')
    @yield('content')
</body>
</html>
