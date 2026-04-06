@extends('layouts.app')
@section('page_title', 'Settings')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Settings</h1>
        <p class="text-slate-500 text-sm mt-0.5">Configure your clinic preferences and system options</p>
    </div>
    <button id="saveAll" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
        <i class="fa-solid fa-floppy-disk"></i> Save All Changes
    </button>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

    {{-- Sidebar nav --}}
    <div class="xl:col-span-1">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3 space-y-0.5 sticky top-20">
            @php
                $sections = [
                    ['id' => 'clinic',        'icon' => 'fa-hospital',          'label' => 'Clinic Info'],
                    ['id' => 'hours',         'icon' => 'fa-clock',             'label' => 'Working Hours'],
                    ['id' => 'services',      'icon' => 'fa-tooth',             'label' => 'Services'],
                    ['id' => 'notifications', 'icon' => 'fa-bell',              'label' => 'Notifications'],
                    ['id' => 'appearance',    'icon' => 'fa-palette',           'label' => 'Appearance'],
                    ['id' => 'security',      'icon' => 'fa-shield-halved',     'label' => 'Security'],
                ];
            @endphp
            @foreach ($sections as $i => $sec)
                <a href="#{{ $sec['id'] }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ $i === 0 ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid {{ $sec['icon'] }} w-4 text-center"></i>
                    {{ $sec['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Main content --}}
    <div class="xl:col-span-3 space-y-6">

        {{-- Clinic Info --}}
        <div id="clinic" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-5 pb-4 border-b border-slate-100">
                <div class="h-8 w-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <i class="fa-solid fa-hospital"></i>
                </div>
                <h2 class="font-semibold text-base text-slate-800">Clinic Information</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Clinic Name</label>
                    <input type="text" value="DentalCare Management System" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                    <input type="tel" value="(02) 8123-4567" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                    <input type="email" value="info@dentalcare.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                    <input type="text" value="123 Dental Ave, Quezon City, Metro Manila" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tax ID / TIN</label>
                    <input type="text" value="123-456-789-000" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Website</label>
                    <input type="url" value="https://dentalcare.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
            </div>
        </div>

        {{-- Working Hours --}}
        <div id="hours" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-5 pb-4 border-b border-slate-100">
                <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <h2 class="font-semibold text-base text-slate-800">Working Hours</h2>
            </div>
            @php
                $days = [
                    ['day' => 'Monday',    'open' => true,  'from' => '09:00', 'to' => '18:00'],
                    ['day' => 'Tuesday',   'open' => true,  'from' => '09:00', 'to' => '18:00'],
                    ['day' => 'Wednesday', 'open' => true,  'from' => '09:00', 'to' => '18:00'],
                    ['day' => 'Thursday',  'open' => true,  'from' => '09:00', 'to' => '18:00'],
                    ['day' => 'Friday',    'open' => true,  'from' => '09:00', 'to' => '18:00'],
                    ['day' => 'Saturday',  'open' => true,  'from' => '09:00', 'to' => '14:00'],
                    ['day' => 'Sunday',    'open' => false, 'from' => '09:00', 'to' => '18:00'],
                ];
            @endphp
            <div class="space-y-3">
                @foreach ($days as $d)
                    <div class="flex items-center gap-4">
                        <div class="w-28 shrink-0">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" {{ $d['open'] ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                                <span class="text-sm font-medium {{ $d['open'] ? 'text-slate-700' : 'text-slate-400' }}">{{ $d['day'] }}</span>
                            </label>
                        </div>
                        <div class="flex items-center gap-2 flex-1 {{ !$d['open'] ? 'opacity-40 pointer-events-none' : '' }}">
                            <input type="time" value="{{ $d['from'] }}"
                                class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                            <span class="text-slate-400 text-sm">to</span>
                            <input type="time" value="{{ $d['to'] }}"
                                class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                        </div>
                        @if (!$d['open'])
                            <span class="text-xs text-slate-400 font-medium shrink-0">Closed</span>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Appointment Slot Duration</label>
                <select class="w-48 px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option>30 minutes</option>
                    <option selected>1 hour</option>
                    <option>1.5 hours</option>
                </select>
            </div>
        </div>

        {{-- Services --}}
        <div id="services" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600">
                        <i class="fa-solid fa-tooth"></i>
                    </div>
                    <h2 class="font-semibold text-base text-slate-800">Services & Pricing</h2>
                </div>
                <button type="button" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Service
                </button>
            </div>
            @php
                $services = [
                    ['name' => 'Consultation',      'price' => 300,   'duration' => '30 min'],
                    ['name' => 'Teeth Cleaning',    'price' => 800,   'duration' => '1 hr'],
                    ['name' => 'Dental Filling',    'price' => 1500,  'duration' => '1 hr'],
                    ['name' => 'Tooth Extraction',  'price' => 2000,  'duration' => '1 hr'],
                    ['name' => 'Root Canal',        'price' => 8500,  'duration' => '2 hrs'],
                    ['name' => 'Teeth Whitening',   'price' => 5000,  'duration' => '1.5 hrs'],
                    ['name' => 'Orthodontics',      'price' => 35000, 'duration' => 'Ongoing'],
                    ['name' => 'X-Ray',             'price' => 350,   'duration' => '15 min'],
                ];
            @endphp
            <div class="space-y-2">
                @foreach ($services as $svc)
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition group">
                        <div class="flex-1 min-w-0">
                            <input type="text" value="{{ $svc['name'] }}"
                                class="font-medium text-slate-800 text-sm bg-transparent border-none outline-none w-full focus:bg-white focus:border focus:border-emerald-300 focus:ring-0 focus:px-2 rounded-lg transition" />
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="text-slate-400 text-sm">₱</span>
                            <input type="number" value="{{ $svc['price'] }}"
                                class="w-20 text-right font-semibold text-slate-800 text-sm bg-transparent border-none outline-none focus:bg-white focus:border focus:border-emerald-300 focus:ring-0 focus:px-2 rounded-lg transition" />
                        </div>
                        <div class="w-20 shrink-0 text-xs text-slate-400 text-right">{{ $svc['duration'] }}</div>
                        <button type="button" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Notifications --}}
        <div id="notifications" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-5 pb-4 border-b border-slate-100">
                <div class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <h2 class="font-semibold text-base text-slate-800">Notifications</h2>
            </div>
            @php
                $notifSettings = [
                    ['label' => 'New appointment booked',     'email' => true,  'sms' => true],
                    ['label' => 'Appointment confirmed',      'email' => true,  'sms' => false],
                    ['label' => 'Appointment cancelled',      'email' => true,  'sms' => true],
                    ['label' => 'Appointment reminder (24h)', 'email' => true,  'sms' => true],
                    ['label' => 'Invoice overdue',            'email' => true,  'sms' => false],
                    ['label' => 'New patient registered',     'email' => false, 'sms' => false],
                ];
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                            <th class="text-left pb-3 font-semibold">Event</th>
                            <th class="text-center pb-3 font-semibold w-20">Email</th>
                            <th class="text-center pb-3 font-semibold w-20">SMS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($notifSettings as $n)
                            <tr>
                                <td class="py-3 text-slate-700">{{ $n['label'] }}</td>
                                <td class="py-3 text-center">
                                    <input type="checkbox" {{ $n['email'] ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                                </td>
                                <td class="py-3 text-center">
                                    <input type="checkbox" {{ $n['sms'] ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-200" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Appearance --}}
        <div id="appearance" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-5 pb-4 border-b border-slate-100">
                <div class="h-8 w-8 rounded-lg bg-violet-50 flex items-center justify-center text-violet-600">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <h2 class="font-semibold text-base text-slate-800">Appearance</h2>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Accent Color</label>
                    <div class="flex items-center gap-3 flex-wrap">
                        @php
                            $colors = [
                                ['name' => 'Emerald', 'class' => 'bg-emerald-500', 'active' => true],
                                ['name' => 'Blue',    'class' => 'bg-blue-500',    'active' => false],
                                ['name' => 'Violet',  'class' => 'bg-violet-500',  'active' => false],
                                ['name' => 'Rose',    'class' => 'bg-rose-500',    'active' => false],
                                ['name' => 'Amber',   'class' => 'bg-amber-500',   'active' => false],
                                ['name' => 'Teal',    'class' => 'bg-teal-500',    'active' => false],
                            ];
                        @endphp
                        @foreach ($colors as $c)
                            <label class="cursor-pointer" title="{{ $c['name'] }}">
                                <input type="radio" name="accent" class="sr-only peer" {{ $c['active'] ? 'checked' : '' }} />
                                <div class="h-8 w-8 rounded-full {{ $c['class'] }} ring-2 ring-offset-2 ring-transparent peer-checked:ring-current transition"></div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sidebar Style</label>
                    <div class="flex gap-3">
                        @foreach (['Compact', 'Default', 'Wide'] as $style)
                            <label class="cursor-pointer">
                                <input type="radio" name="sidebar" class="sr-only peer" {{ $style === 'Default' ? 'checked' : '' }} />
                                <div class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:bg-slate-50 transition">
                                    {{ $style }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Security --}}
        <div id="security" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-5 pb-4 border-b border-slate-100">
                <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center text-red-500">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="font-semibold text-base text-slate-800">Security</h2>
            </div>
            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Session Timeout</div>
                        <div class="text-xs text-slate-400 mt-0.5">Automatically log out inactive users</div>
                    </div>
                    <select class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                        <option>15 minutes</option>
                        <option selected>30 minutes</option>
                        <option>1 hour</option>
                        <option>Never</option>
                    </select>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Require Strong Passwords</div>
                        <div class="text-xs text-slate-400 mt-0.5">Min. 8 characters, uppercase, number, and symbol</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked />
                        <div class="w-10 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-emerald-200 rounded-full peer peer-checked:bg-emerald-500 transition"></div>
                        <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-4"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <div>
                        <div class="text-sm font-medium text-slate-700">Login Activity Log</div>
                        <div class="text-xs text-slate-400 mt-0.5">Keep a log of all staff logins</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked />
                        <div class="w-10 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-emerald-200 rounded-full peer peer-checked:bg-emerald-500 transition"></div>
                        <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-4"></div>
                    </label>
                </div>
                <div class="pt-4 border-t border-slate-100">
                    <div class="text-sm font-medium text-slate-700 mb-3">Change Admin Password</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="password" placeholder="Current password" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                        <input type="password" placeholder="New password" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <button type="button" class="mt-3 px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition">
                        Update Password
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Highlight active section in sidebar on scroll
    const sections = document.querySelectorAll('[id]');
    const navLinks = document.querySelectorAll('a[href^="#"]');
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(sec => {
            if (window.scrollY >= sec.offsetTop - 100) current = sec.id;
        });
        navLinks.forEach(link => {
            const active = link.getAttribute('href') === '#' + current;
            link.classList.toggle('bg-emerald-50', active);
            link.classList.toggle('text-emerald-700', active);
            link.classList.toggle('text-slate-600', !active);
        });
    });

    // Save all button feedback
    document.getElementById('saveAll').addEventListener('click', function() {
        this.innerHTML = '<i class="fa-solid fa-circle-check mr-1"></i> Saved!';
        this.classList.replace('bg-emerald-500', 'bg-emerald-600');
        setTimeout(() => {
            this.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save All Changes';
            this.classList.replace('bg-emerald-600', 'bg-emerald-500');
        }, 2000);
    });
</script>
@endpush
