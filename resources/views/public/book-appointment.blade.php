<!DOCTYPE html>
<html lang="en">
<head>
    <title>Book an Appointment — DentalCare</title>
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
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white"><i class="fa-solid fa-tooth"></i></div>
                <div>
                    <div class="font-bold leading-tight text-slate-800">DentalCare</div>
                    <div class="text-[10px] text-slate-500">Clinic & Management</div>
                </div>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-slate-500 hidden sm:block"><i class="fa-solid fa-phone mr-1"></i> (02) 8123-4567</span>
                <a href="/login" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition text-sm">Staff Login</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white py-12">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <div class="text-4xl mb-3"><i class="fa-solid fa-tooth"></i></div>
            <h1 class="text-3xl font-bold">Book Your Appointment</h1>
            <p class="mt-2 text-emerald-100 max-w-md mx-auto">Fill out the form below and our team will confirm your schedule within 24 hours.</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="max-w-2xl mx-auto px-6 py-10">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
            @php
                $errClass = fn ($field) => $errors->has($field) ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200';
            @endphp

            <form action="{{ route('public.book.store') }}" method="POST" class="space-y-6">
                @csrf

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
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="contact_number" value="{{ old('contact_number') }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('contact_number') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                            @error('contact_number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="juan@email.com" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('email') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Appointment --}}
                <div>
                    <h2 class="font-semibold text-base text-slate-800 mb-4 pb-3 border-b border-slate-100">Appointment Details</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Preferred Date <span class="text-red-500">*</span></label>
                            <input type="date" name="appointment_date" value="{{ old('appointment_date') }}" min="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('appointment_date') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                            @error('appointment_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Preferred Time</label>
                            <select name="appointment_time" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('appointment_time') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition">
                                <option {{ old('appointment_time') === 'Morning (9 AM – 12 PM)' ? 'selected' : '' }}>Morning (9 AM – 12 PM)</option>
                                <option {{ old('appointment_time') === 'Afternoon (1 PM – 5 PM)' ? 'selected' : '' }}>Afternoon (1 PM – 5 PM)</option>
                            </select>
                            @error('appointment_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Service Needed <span class="text-red-500">*</span></label>
                            <select name="service" class="w-full px-4 py-2.5 rounded-xl border {{ $errClass('service') }} bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required>
                                <option value="">— Select a service —</option>
                                @foreach ($services as $svc)
                                    <option {{ old('service') === $svc ? 'selected' : '' }}>{{ $svc }}</option>
                                @endforeach
                            </select>
                            @if ($services->isEmpty())
                                <p class="text-xs text-amber-600 mt-1">Online booking is temporarily unavailable. Please call us to schedule.</p>
                            @endif
                            @error('service') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
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

                <button type="submit" class="w-full py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition shadow-sm">
                    Submit Appointment Request
                </button>
            </form>
        </div>

        {{-- Info cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">
            @php
                $info = [
                    ['icon' => '<i class="fa-solid fa-clock text-emerald-500"></i>',        'title' => 'Clinic Hours',  'body' => 'Mon–Sat: 9 AM – 6 PM'],
                    ['icon' => '<i class="fa-solid fa-phone text-emerald-500"></i>',         'title' => 'Call Us',       'body' => '(02) 8123-4567'],
                    ['icon' => '<i class="fa-solid fa-location-dot text-emerald-500"></i>',  'title' => 'Our Location',  'body' => '123 Dental Ave, Quezon City'],
                ];
            @endphp
            @foreach ($info as $i)
                <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center">
                    <div class="text-2xl mb-2">{!! $i['icon'] !!}</div>
                    <div class="font-semibold text-sm text-slate-800">{{ $i['title'] }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ $i['body'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 bg-white py-6 mt-8">
        <p class="text-center text-xs text-slate-400">&copy; {{ date('Y') }} DentalCare Management System. All rights reserved.</p>
    </footer>

</body>
</html>
