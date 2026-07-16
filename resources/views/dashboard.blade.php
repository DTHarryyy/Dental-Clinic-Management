@extends('layouts.app')
@section('page_title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
    <p class="text-slate-500 mt-1 text-sm">Welcome back, {{ auth()->user()->name }}! Here's what's happening today.</p>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
    @php
        $stats = [
              ['label' => 'Total Patients',        'value' => $totalPatients,                          'icon' => '<i class="fa-solid fa-users"></i>',          'bg' => 'bg-emerald-50', 'color' => 'text-emerald-600', 'link' => route('patients.index')],
              ['label' => "Today's Appointments",  'value' => $todaysAppointments->count(),            'icon' => '<i class="fa-solid fa-calendar-days"></i>', 'bg' => 'bg-blue-50',    'color' => 'text-blue-600',    'link' => route('appointments.index')],
              ['label' => 'Monthly Revenue',        'value' => '₱' . number_format($monthlyRevenue, 2), 'icon' => '<i class="fa-solid fa-money-bill-wave"></i>','bg' => 'bg-emerald-50', 'color' => 'text-emerald-600', 'link' => route('billing.index')],
        ];
    @endphp

    @foreach ($stats as $stat)
        <a href="{{ $stat['link'] }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition block">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-slate-500 font-medium">{{ $stat['label'] }}</div>
                    <div class="text-3xl font-bold mt-2 text-slate-800">{{ $stat['value'] }}</div>
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
            <a href="{{ route('appointments.index') }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">View All →</a>
        </div>

        @php
            $statusColors = [
                'confirmed' => 'bg-emerald-100 text-emerald-700',
                'pending'   => 'bg-amber-100 text-amber-700',
                'completed' => 'bg-blue-100 text-blue-700',
                'cancelled' => 'bg-red-100 text-red-700',
            ];
        @endphp

        <div class="space-y-3">
            @forelse ($todaysAppointments as $appt)
                <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition">
                    <div class="w-20 shrink-0">
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">{{ $appt->appointment_time ?? '—' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-slate-800">{{ $appt->full_name }}</div>
                        <div class="text-xs text-slate-500">{{ $appt->service }}</div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg {{ $statusColors[$appt->status] }}">{{ ucfirst($appt->status) }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400 text-center py-6">No appointments scheduled for today.</p>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-semibold text-base text-slate-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('patients.create') }}"     class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 transition shadow-sm"><i class="fa-solid fa-user-plus mr-1"></i> Add Patient</a>
            <a href="{{ route('appointments.create') }}" class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 transition shadow-sm"><i class="fa-solid fa-calendar-plus mr-1"></i> Book Appt.</a>
            <a href="{{ route('billing.create') }}"      class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-violet-500 hover:bg-violet-600 transition shadow-sm"><i class="fa-solid fa-file-invoice-dollar mr-1"></i> Invoice</a>
            <a href="{{ route('records.create') }}"      class="rounded-xl py-5 text-center text-sm font-semibold text-white bg-teal-500 hover:bg-teal-600 transition shadow-sm"><i class="fa-solid fa-stethoscope mr-1"></i> Record</a>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="{{ route('public.book') }}" target="_blank" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl border border-emerald-200 text-emerald-700 text-sm font-semibold hover:bg-emerald-50 transition">
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
            @if (auth()->user()->role === 'admin')
                <a href="{{ route('reports') }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">Full Report →</a>
            @endif
        </div>
        <canvas id="revenueChart" height="110"></canvas>
    </div>

    {{-- Recent patients --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-base text-slate-800">Recent Patients</h2>
            <a href="{{ route('patients.index') }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">All →</a>
        </div>

        <div class="space-y-3">
            @forelse ($recentPatients as $p)
                <a href="{{ route('patients.show', $p) }}" class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition">
                    <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ strtoupper(substr($p->first_name, 0, 1) . substr($p->last_name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-sm text-slate-800">{{ $p->name }}</div>
                        <div class="text-xs text-slate-500">{{ $p->created_at->diffForHumans() }}</div>
                    </div>
                </a>
            @empty
                <p class="text-sm text-slate-400 text-center py-6">No patients yet.</p>
            @endforelse
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
            labels: {!! $revenueTrend->pluck('label')->toJson() !!},
            datasets: [{
                label: 'Revenue',
                data: {!! $revenueTrend->pluck('value')->toJson() !!},
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
