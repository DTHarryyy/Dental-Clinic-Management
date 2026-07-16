<!DOCTYPE html>
<html lang="en">
<head>
    <title>Appointment Submitted — DentalCare</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>html, body { font-family: Inter, system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">

    <nav class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white"><i class="fa-solid fa-tooth"></i></div>
            <div>
                <div class="font-bold leading-tight text-slate-800">DentalCare</div>
                <div class="text-[10px] text-slate-500">Clinic & Management</div>
            </div>
        </div>
    </nav>

    @php $appointment = session('appointment'); @endphp

    <div class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-md w-full text-center">
            {{-- Success icon --}}
            <div class="h-24 w-24 rounded-full bg-emerald-100 flex items-center justify-center text-5xl text-emerald-500 mx-auto animate-bounce">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mt-6">Appointment Submitted!</h1>
            <p class="text-slate-500 mt-2 text-sm leading-relaxed">
                Thank you! Your appointment request has been received. Our team will contact you within <strong>24 hours</strong> to confirm your schedule.
            </p>

            @if ($appointment)
                {{-- Summary card --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mt-6 text-left space-y-3">
                    <h2 class="font-semibold text-sm text-slate-700 mb-3">Request Summary</h2>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Name</span>
                        <span class="font-medium text-slate-700">{{ $appointment->full_name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Service</span>
                        <span class="font-medium text-slate-700">{{ $appointment->service }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Preferred Date</span>
                        <span class="font-medium text-slate-700">{{ $appointment->appointment_date->format('F j, Y') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Preferred Time</span>
                        <span class="font-medium text-slate-700">{{ $appointment->appointment_time ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Status</span>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-lg bg-amber-100 text-amber-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 inline-block"></span> Pending Confirmation
                        </span>
                    </div>
                </div>

                <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 mt-4 text-sm text-emerald-700">
                    <i class="fa-solid fa-phone mr-1"></i> We'll reach you at <strong>{{ $appointment->contact_number }}</strong>. Please keep your phone line open.
                </div>
            @endif

            <a href="/book-appointment" class="block w-full mt-6 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition shadow-sm">
                Book Another Appointment
            </a>
            <a href="/" class="block w-full mt-3 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition text-sm">
                Back to Home
            </a>
        </div>
    </div>

    <footer class="border-t border-slate-200 bg-white py-5">
        <p class="text-center text-xs text-slate-400">&copy; {{ date('Y') }} DentalCare Management System</p>
    </footer>

</body>
</html>
