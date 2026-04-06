@extends('layouts.app')
@section('page_title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
    <p class="text-slate-500 mt-1 text-sm">Welcome back, Dr. Reyes! Here's what's happening today.</p>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
    @php
        $stats = [
              ['label' => 'Total Patients',        'value' => '54',     'change' => '+3% vs last month', 'up' => true,  'icon' => '<i class="fa-solid fa-users"></i>',          'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600', 'link' => '/patients'],
              ['label' => "Today's Appointments",  'value' => '3',       'change' => '2 confirmed',         'up' => true,  'icon' => '<i class="fa-solid fa-calendar-days"></i>', 'bg' => 'bg-blue-50',    'color' => 'text-blue-600',    'link' => '/appointments'],
              ['label' => 'Monthly Revenue',        'value' => '₱21,500', 'change' => '+2% vs last month',  'up' => true,  'icon' => '<i class="fa-solid fa-money-bill-wave"></i>','bg' => 'bg-emerald-50', 'color' => 'text-emerald-600', 'link' => '/billing'],
        ];
    @endphp

    @foreach ($stats as $stat)
        <a href="{{ $stat['link'] }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition block">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-slate-500 font-medium">{{ $stat['label'] }}</div>
                    <div class="text-3xl font-bold mt-2 text-slate-800">{{ $stat['value'] }}</div>
                    <div class="mt-2 text-xs flex items-center gap-1 {{ $stat['up'] ? 'text-emerald-600' : 'text-amber-600' }}">
                        <span>{{ $stat['up'] ? '↗' : '↘' }}</span>
                        <span class="font-semibold">{{ $stat['change'] }}</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-2xl {{ $stat['bg'] }} flex items-center justify-center {{ $stat['color'] }} text-xl shrink-0">
                    {!! $stat['icon'] !!}
                </div>
            </div>
        </a>
    @endforeach
</div>

{{-- Middle row --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mt-6">

    {{-- Today's Schedule --}}
    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-semibold text-base text-slate-800">Today's Schedule</h2>
            <a href="/appointments" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">View All →</a>
        </div>

        @php
            $schedule = [
                 ['time' => '09:30 AM', 'patient' => 'Carmela Villanueva', 'service' => 'Dental Checkup',    'status' => 'Confirmed', 'statusColor' => 'bg-emerald-100 text-emerald-700'],
                 ['time' => '11:00 AM', 'patient' => 'Ramon Pascual',     'service' => 'Tooth Extraction',  'status' => 'Pending',   'statusColor' => 'bg-amber-100 text-amber-700'],
                 ['time' => '02:00 PM', 'patient' => 'Liza Mercado',      'service' => 'Braces Adjustment', 'status' => 'Confirmed', 'statusColor' => 'bg-emerald-100 text-emerald-700'],
            ];
        @endphp

        <div class="space-y-3">
            @foreach ($schedule as $appt)
                <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition">
                    <div class="w-20 shrink-0">
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">{{ $appt['time'] }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-slate-800">{{ $appt['patient'] }}</div>
                        <div class="text-xs text-slate-500">{{ $appt['service'] }}</div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $appt['statusColor'] }}">{{ $appt['status'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="/patients/create"     class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 transition shadow-sm"><i class="fa-solid fa-user-plus mr-1"></i> Add Patient</a>
            <a href="/appointments/create" class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 transition shadow-sm"><i class="fa-solid fa-calendar-plus mr-1"></i> Book Appt.</a>
            <a href="/billing/create"      class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-violet-500 hover:bg-violet-600 transition shadow-sm"><i class="fa-solid fa-file-invoice-dollar mr-1"></i> Invoice</a>
            <a href="/records/create"      class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-teal-500 hover:bg-teal-600 transition shadow-sm"><i class="fa-solid fa-stethoscope mr-1"></i> Record</a>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="/book-appointment" target="_blank" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl border border-emerald-200 text-emerald-700 text-sm font-semibold hover:bg-emerald-50 transition">
                <i class="fa-solid fa-clipboard-list"></i> Patient Booking Link
            </a>
        </div>
    </div>
</div>

{{-- Bottom row --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mt-6">

    {{-- Revenue chart --}}
    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-base text-slate-800">Revenue Trend</h2>
            <a href="/reports" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">Full Report →</a>
        </div>
        <canvas id="revenueChart" height="110"></canvas>
    </div>

    {{-- Recent patients --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-base text-slate-800">Recent Patients</h2>
            <a href="/patients" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">All →</a>
        </div>

        @php
            $recent = [
                 ['name' => 'Carmela Villanueva', 'date' => 'Today, 9:30 AM',  'initials' => 'CV', 'color' => 'bg-emerald-100 text-emerald-700'],
                 ['name' => 'Ramon Pascual',      'date' => 'Today, 11:00 AM', 'initials' => 'RP', 'color' => 'bg-blue-100 text-blue-700'],
                 ['name' => 'Liza Mercado',       'date' => 'Yesterday',       'initials' => 'LM', 'color' => 'bg-violet-100 text-violet-700'],
            ];
        @endphp

        <div class="space-y-3">
            @foreach ($recent as $p)
                <a href="/patients/1" class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition">
                    <div class="h-9 w-9 rounded-full {{ $p['color'] }} flex items-center justify-center font-bold text-xs shrink-0">
                        {{ $p['initials'] }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-sm text-slate-800">{{ $p['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ $p['date'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('revenueChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
            datasets: [{
                label: 'Revenue',
                data: [38000, 44000, 51000, 47000, 55000, 48200],
                tension: 0.4,
                fill: true,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.08)',
                pointBackgroundColor: '#10b981',
                pointRadius: 4,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    ticks: { callback: v => '₱' + (v / 1000) + 'k' },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
