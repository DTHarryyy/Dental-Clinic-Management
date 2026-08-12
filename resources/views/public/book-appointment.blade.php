<!DOCTYPE html>
<html lang="en">
<head>
    <title>Book an Appointment — Aquilizan Dental Clinic</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        html, body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    {{-- Validation errors are shown inline on this form instead; avoid double-reporting them via the toast. --}}
    @if (! $errors->any())
        @include('components.toast')
    @endif

    {{-- Navbar --}}
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl object-contain" />
                <div>
                    <div class="font-bold leading-tight text-slate-800">Aquilizan Dental Clinic</div>
                    <div class="text-[10px] text-slate-500">Open daily, 8:00 AM–5:00 PM</div>
                </div>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="mailto:annee_aquilizan@gmail.com" class="text-slate-500 hidden sm:block hover:text-emerald-600"><i class="fa-solid fa-envelope mr-1"></i> annee_aquilizan@gmail.com</a>
                <a href="/login" class="inline-flex min-h-11 items-center whitespace-nowrap px-3 sm:px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition text-sm">Staff Login</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white py-7 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <div class="text-3xl sm:text-4xl mb-2 sm:mb-3"><i class="fa-solid fa-tooth"></i></div>
            <h1 class="text-2xl sm:text-3xl font-bold">Book Your Appointment</h1>
                    <p class="mt-2 text-emerald-100 max-w-md mx-auto">Choose your preferred date and an exact available time. We’ll email you after approval.</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="max-w-2xl mx-auto px-3 sm:px-6 py-5 sm:py-10">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-8">
            @php
                $errClass = fn ($field) => $errors->has($field) ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200';
            @endphp

            <form action="{{ route('public.book.store') }}" method="POST" class="space-y-6" x-data="{ submitting: false }" x-on:submit="submitting = true" data-public-booking data-availability-url="{{ route('public.book.availability') }}">
                @csrf
                <input type="hidden" name="preferred_time_window" value="morning">

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
                        <i class="fa-solid fa-circle-exclamation mr-1.5"></i> Please fix the highlighted fields below.
                    </div>
                @endif

                {{-- Personal --}}
                <div>
                    <h2 class="font-semibold text-base text-slate-800 mb-4 pb-3 border-b border-slate-100">Your Information</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Juan Dela Cruz" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('full_name') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                            @error('full_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="juan@email.com" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('email') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Appointment --}}
                <div>
                    <h2 class="font-semibold text-base text-slate-800 mb-4 pb-3 border-b border-slate-100">Appointment Details</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Appointment Date <span class="text-red-500">*</span></label>
                            <input type="date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('preferred_date') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                            @error('preferred_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Exact Time <span class="text-red-500">*</span></label>
                            <select name="requested_start_at" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('requested_start_at') }} bg-slate-50 text-sm" required disabled><option value="">Choose date and services first</option></select>
                            <p data-public-slot-status class="mt-1 text-xs text-slate-500"></p>
                            @error('requested_start_at') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2" x-data="{ selected: @js(array_map('intval', old('service_ids', []))) }">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Services Needed <span class="text-red-500">*</span></label>
                            <div class="grid sm:grid-cols-2 gap-3">
                                @foreach ($services as $svc)
                                    <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-3 hover:border-emerald-300">
                                        <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}" x-model.number="selected" @checked(in_array($svc->id, old('service_ids', []))) class="mt-1 rounded border-slate-300 text-emerald-600">
                                        <span class="block text-sm font-semibold text-slate-700">{{ $svc->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($services->isEmpty())
                                <p class="text-xs text-amber-600 mt-1">Online booking is temporarily unavailable. Please email us to schedule.</p>
                            @endif
                            @error('service_ids') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            @error('service_ids.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Describe Your Concern</label>
                            <textarea name="concern" rows="3" placeholder="Briefly describe your dental concern or reason for visit..." class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('concern') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition resize-none">{{ old('concern') }}</textarea>
                            @error('concern') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Consent --}}
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" required />
                        <p class="text-xs text-slate-600">
                            I agree to the collection and use of my personal information for appointment scheduling purposes, in accordance with the clinic's privacy policy.
                        </p>
                    </label>
                </div>

                <button type="submit" :disabled="submitting" class="flex w-full items-center justify-center gap-2 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition shadow-sm disabled:cursor-not-allowed disabled:opacity-70">
                    <svg x-show="submitting" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0A12 12 0 0 0 0 12h4Z"></path>
                    </svg>
                    <span x-text="submitting ? 'Submitting request…' : 'Submit Appointment Request'">Submit Appointment Request</span>
                </button>
            </form>
        </div>

    </div>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 bg-white py-6 mt-8">
        <p class="text-center text-xs text-slate-400">&copy; {{ date('Y') }} Aquilizan Dental Clinic. All rights reserved.</p>
    </footer>

</body>
</html>
