@extends('layouts.app')
@section('page_title', 'Reports')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Reports</h1>
        <p class="text-slate-500 text-sm mt-0.5">Analytics and performance overview</p>
    </div>
    <div class="flex items-center gap-3">
        <button class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-file-arrow-down mr-1"></i> Export CSV</button>
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-print mr-1"></i> Print</button>
    </div>
</div>

{{-- Date filter bar --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-center">
    <span class="text-sm font-medium text-slate-600">Date Range:</span>
    <div class="flex items-center gap-2">
        <input type="date" value="2026-03-01" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
        <span class="text-slate-400 text-sm">to</span>
        <input type="date" value="2026-03-24" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
    </div>
    <div class="flex gap-2">
        @foreach (['Today', 'This Week', 'This Month', 'This Year'] as $period)
            <button class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $period === 'This Month' ? 'bg-emerald-500 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }} transition">
                {{ $period }}
            </button>
        @endforeach
    </div>
    <button class="ml-auto px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition">Apply</button>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $kpis = [
            ['label' => 'Total Revenue',      'value' => '₱48,200', 'sub' => '+8% vs Feb',  'up' => true,  'icon' => '<i class="fa-solid fa-money-bill-wave"></i>', 'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Appointments',       'value' => '67',      'sub' => '+14 vs Feb',   'up' => true,  'icon' => '<i class="fa-solid fa-calendar-days"></i>',   'bg' => 'bg-blue-50',    'color' => 'text-blue-600'],
            ['label' => 'New Patients',       'value' => '12',      'sub' => '+3 vs Feb',    'up' => true,  'icon' => '<i class="fa-solid fa-users"></i>',            'bg' => 'bg-violet-50',  'color' => 'text-violet-600'],
            ['label' => 'Avg. Bill / Visit',  'value' => '₱719',    'sub' => '-₱12 vs Feb',  'up' => false, 'icon' => '<i class="fa-solid fa-chart-bar"></i>',        'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
        ];
    @endphp
    @foreach ($kpis as $kpi)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs text-slate-500 font-medium">{{ $kpi['label'] }}</div>
                    <div class="text-2xl font-bold mt-2 text-slate-800">{{ $kpi['value'] }}</div>
                    <div class="mt-1.5 text-xs font-semibold {{ $kpi['up'] ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $kpi['up'] ? '↗' : '↘' }} {{ $kpi['sub'] }}
                    </div>
                </div>
                <div class="h-10 w-10 rounded-xl {{ $kpi['bg'] }} flex items-center justify-center {{ $kpi['color'] }} text-lg shrink-0">{!! $kpi['icon'] !!}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- Charts row --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

    {{-- Revenue chart --}}
    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-base text-slate-800">Revenue (Last 6 Months)</h2>
            <select class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none">
                <option>Bar</option>
                <option>Line</option>
            </select>
        </div>
        <canvas id="revenueChart" height="120"></canvas>
    </div>

    {{-- Service breakdown --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Service Breakdown</h2>
        <canvas id="serviceChart" height="180"></canvas>
        <div class="mt-4 space-y-2">
            @php
                $services = [
                    ['name' => 'Teeth Cleaning',  'pct' => 28, 'color' => 'bg-emerald-500'],
                    ['name' => 'Dental Filling',  'pct' => 22, 'color' => 'bg-blue-500'],
                    ['name' => 'Consultation',    'pct' => 18, 'color' => 'bg-violet-500'],
                    ['name' => 'Root Canal',      'pct' => 14, 'color' => 'bg-amber-500'],
                    ['name' => 'Other',           'pct' => 18, 'color' => 'bg-slate-400'],
                ];
            @endphp
            @foreach ($services as $svc)
                <div class="flex items-center gap-2 text-xs">
                    <div class="h-2 w-2 rounded-full {{ $svc['color'] }} shrink-0"></div>
                    <span class="text-slate-600 flex-1">{{ $svc['name'] }}</span>
                    <span class="font-semibold text-slate-700">{{ $svc['pct'] }}%</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Bottom row --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Appointments trend --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Appointment Volume</h2>
        <canvas id="apptChart" height="130"></canvas>
    </div>

    {{-- Top services table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Top Services — March 2026</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                    <th class="text-left pb-2 font-semibold">Service</th>
                    <th class="text-right pb-2 font-semibold">Sessions</th>
                    <th class="text-right pb-2 font-semibold">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @php
                    $topServices = [
                        ['name' => 'Teeth Cleaning',  'sessions' => 19, 'revenue' => '₱15,200'],
                        ['name' => 'Dental Filling',  'sessions' => 15, 'revenue' => '₱22,500'],
                        ['name' => 'Consultation',    'sessions' => 12, 'revenue' => '₱3,600'],
                        ['name' => 'Root Canal',      'sessions' => 9,  'revenue' => '₱76,500'],
                        ['name' => 'Tooth Extraction','sessions' => 7,  'revenue' => '₱14,000'],
                    ];
                @endphp
                @foreach ($topServices as $i => $ts)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-400 w-4">{{ $i + 1 }}</span>
                                <span class="text-slate-700">{{ $ts['name'] }}</span>
                            </div>
                        </td>
                        <td class="py-3 text-right text-slate-600">{{ $ts['sessions'] }}</td>
                        <td class="py-3 text-right font-semibold text-slate-800">{{ $ts['revenue'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Revenue Bar Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
            datasets: [{
                label: 'Revenue',
                data: [38000, 44000, 51000, 47000, 44600, 48200],
                backgroundColor: 'rgba(16,185,129,0.15)',
                borderColor: '#10b981',
                borderWidth: 2,
                borderRadius: 8,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { callback: v => '₱' + (v/1000) + 'k' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Service Doughnut
    new Chart(document.getElementById('serviceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Cleaning', 'Filling', 'Consult', 'Root Canal', 'Other'],
            datasets: [{
                data: [28, 22, 18, 14, 18],
                backgroundColor: ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#94a3b8'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            cutout: '65%',
        }
    });

    // Appointments Line
    new Chart(document.getElementById('apptChart'), {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Appointments',
                data: [14, 18, 21, 14],
                tension: 0.4,
                fill: true,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                pointBackgroundColor: '#3b82f6',
                pointRadius: 5,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
