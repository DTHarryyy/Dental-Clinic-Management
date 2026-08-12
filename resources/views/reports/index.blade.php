@extends('layouts.app')
@section('page_title', 'Reports')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="text-slate-500 text-sm mt-0.5">Analytics and performance overview</p>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-sm transition"><i class="fa-solid fa-print mr-1"></i> Print</button>
    </div>
</div>

{{-- Date filter bar --}}
<form method="GET" data-auto-filter="reports" class="filter-bar filter-controls mb-6">
    <span class="text-sm font-medium text-slate-600">Date Range:</span>
    <div class="grid w-full grid-cols-[1fr_auto_1fr] items-center gap-2 sm:flex sm:w-auto">
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="filter-control" />
        <span class="text-slate-400 text-sm">to</span>
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="filter-control" />
    </div>
    <div class="flex max-w-full gap-2 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible">
        @php
            $periods = [
                'Today' => [now()->startOfDay()->format('Y-m-d'), now()->endOfDay()->format('Y-m-d')],
                'This Week' => [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')],
                'This Month' => [now()->startOfMonth()->format('Y-m-d'), now()->format('Y-m-d')],
                'This Year' => [now()->startOfYear()->format('Y-m-d'), now()->format('Y-m-d')],
            ];
        @endphp
        @foreach ($periods as $label => $range)
            <a href="{{ route('reports', ['from' => $range[0], 'to' => $range[1]]) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $from->format('Y-m-d') === $range[0] && $to->format('Y-m-d') === $range[1] ? 'bg-emerald-500 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }} transition">
                {{ $label }}
            </a>
        @endforeach
    </div>
</form>

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    @php
        $kpis = [
            ['label' => 'Total Revenue',      'value' => '₱' . number_format($revenue, 2), 'icon' => '<i class="fa-solid fa-money-bill-wave"></i>', 'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600'],
            ['label' => 'Appointments',       'value' => $appointmentsCount,                'icon' => '<i class="fa-solid fa-calendar-days"></i>',   'bg' => 'bg-blue-50',    'color' => 'text-blue-600'],
            ['label' => 'New Patients',       'value' => $newPatients,                      'icon' => '<i class="fa-solid fa-users"></i>',            'bg' => 'bg-violet-50',  'color' => 'text-violet-600'],
            ['label' => 'Avg. Bill / Invoice','value' => '₱' . number_format($avgBill, 2), 'icon' => '<i class="fa-solid fa-chart-bar"></i>',        'bg' => 'bg-amber-50',   'color' => 'text-amber-600'],
        ];
    @endphp
    @foreach ($kpis as $kpi)
        <div class="min-w-0 bg-white rounded-2xl border border-slate-200 shadow-sm p-3 sm:p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs text-slate-500 font-medium">{{ $kpi['label'] }}</div>
                    <div class="break-content text-lg sm:text-2xl font-bold mt-2 text-slate-800">{{ $kpi['value'] }}</div>
                </div>
                <div class="hidden h-10 w-10 rounded-xl {{ $kpi['bg'] }} sm:flex items-center justify-center {{ $kpi['color'] }} text-lg shrink-0">{!! $kpi['icon'] !!}</div>
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
        </div>
        <canvas id="revenueChart" height="120"></canvas>
    </div>

    {{-- Service breakdown --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Service Breakdown</h2>
        <canvas id="serviceChart" height="180"></canvas>
        <div class="mt-4 space-y-2">
            @php $palette = ['bg-emerald-500', 'bg-blue-500', 'bg-violet-500', 'bg-amber-500', 'bg-slate-400', 'bg-teal-500']; @endphp
            @forelse ($serviceBreakdown->take(6) as $i => $svc)
                <div class="flex items-center gap-2 text-xs">
                    <div class="h-2 w-2 rounded-full {{ $palette[$i % count($palette)] }} shrink-0"></div>
                    <span class="text-slate-600 flex-1">{{ $svc->procedure }}</span>
                    <span class="font-semibold text-slate-700">{{ round($svc->sessions / $totalSessions * 100) }}%</span>
                </div>
            @empty
                <p class="text-xs text-slate-400">No treatment data for this period.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Bottom row --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Appointments trend --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Appointment Volume (Last 4 Weeks)</h2>
        <canvas id="apptChart" height="130"></canvas>
    </div>

    {{-- Top services table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Top Services — {{ $from->format('M j') }} to {{ $to->format('M j, Y') }}</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                    <th class="text-left pb-2 font-semibold">Service</th>
                    <th class="text-right pb-2 font-semibold">Sessions</th>
                    <th class="text-right pb-2 font-semibold">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($serviceBreakdown as $i => $ts)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-400 w-4">{{ $i + 1 }}</span>
                                <span class="text-slate-700">{{ $ts->procedure }}</span>
                            </div>
                        </td>
                        <td class="py-3 text-right text-slate-600">{{ $ts->sessions }}</td>
                        <td class="py-3 text-right font-semibold text-slate-800">₱{{ number_format($ts->revenue, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-slate-400">No data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: {!! $revenueTrend->pluck('label')->toJson() !!},
            datasets: [{
                label: 'Revenue',
                data: {!! $revenueTrend->pluck('value')->toJson() !!},
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

    new Chart(document.getElementById('serviceChart'), {
        type: 'doughnut',
        data: {
            labels: {!! $serviceBreakdown->take(6)->pluck('procedure')->toJson() !!},
            datasets: [{
                data: {!! $serviceBreakdown->take(6)->pluck('sessions')->toJson() !!},
                backgroundColor: ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#94a3b8','#14b8a6'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            cutout: '65%',
        }
    });

    new Chart(document.getElementById('apptChart'), {
        type: 'line',
        data: {
            labels: {!! $appointmentVolume->pluck('label')->toJson() !!},
            datasets: [{
                label: 'Appointments',
                data: {!! $appointmentVolume->pluck('value')->toJson() !!},
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
