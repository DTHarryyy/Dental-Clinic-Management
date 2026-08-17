<!DOCTYPE html>
<html lang="en">
<head>
    <title>Page Not Found — DentalCare</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="{{ asset('images/aquilizan-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>html, body { font-family: Inter, system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">

    <nav class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center gap-3">
            <img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-12 w-12 rounded-xl object-contain" />
            <div>
                <div class="font-bold leading-tight text-slate-800">Aquilizan Dental Clinic</div>
                <div class="text-[10px] text-slate-500">Open daily, 8:00 AM–5:00 PM</div>
            </div>
        </div>
    </nav>

    <div class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-md w-full text-center">
            <div class="h-24 w-24 rounded-full bg-emerald-100 flex items-center justify-center text-5xl text-emerald-600 mx-auto">
                <i class="fa-solid fa-tooth"></i>
            </div>

            <h1 class="text-5xl font-bold text-slate-800 mt-6">404</h1>
            <h2 class="text-xl font-semibold text-slate-800 mt-2">Page Not Found</h2>
            <p class="text-slate-500 mt-2 text-sm leading-relaxed">
                Sorry, we couldn't find the page you're looking for. It may have been moved or no longer exists.
            </p>

            <div class="mt-6">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-600">
                        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                    </a>
                @else
                    <a href="{{ route('public.book') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-600">
                        <i class="fa-solid fa-arrow-left"></i> Back to Booking
                    </a>
                @endauth
            </div>
        </div>
    </div>

</body>
</html>
