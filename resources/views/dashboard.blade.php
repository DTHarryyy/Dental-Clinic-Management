@extends('layouts.app')
@section('page_title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="text-slate-500 mt-1">Welcome back! Here's what's happening today.</p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Total Patients</div>
                    <div class="text-3xl font-bold mt-2">5</div>
                    <div class="mt-2 text-sm text-emerald-600 flex items-center gap-2">
                        <span>↗</span><span class="font-semibold">+12%</span><span class="text-slate-400">vs last month</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">👥</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Today's Appointments</div>
                    <div class="text-3xl font-bold mt-2">0</div>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-xl">📅</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Monthly Revenue</div>
                    <div class="text-3xl font-bold mt-2">₱9,500</div>
                    <div class="mt-2 text-sm text-emerald-600 flex items-center gap-2">
                        <span>↗</span><span class="font-semibold">+8%</span><span class="text-slate-400">vs last month</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">$</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-slate-500 font-medium">Low Stock Alerts</div>
                    <div class="text-3xl font-bold mt-2">4</div>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 text-xl">⚠️</div>
            </div>
        </div>
    </div>

    {{-- Middle row --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mt-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border shadow-sm p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-lg">Today's Schedule</h2>
                <a href="#" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">View All</a>
            </div>

            <div class="mt-10 flex flex-col items-center justify-center text-center text-slate-500">
                <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center text-2xl">🕒</div>
                <div class="mt-4 font-medium">No appointments scheduled for today</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-6">
            <h2 class="font-semibold text-lg">Quick Actions</h2>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <button class="rounded-xl py-5 font-semibold text-white bg-emerald-500 hover:bg-emerald-600 transition">Add Patient</button>
                <button class="rounded-xl py-5 font-semibold text-white bg-blue-500 hover:bg-blue-600 transition">Book Appointment</button>
                <button class="rounded-xl py-5 font-semibold text-white bg-green-500 hover:bg-green-600 transition">Create Invoice</button>
                <button class="rounded-xl py-5 font-semibold text-white bg-purple-500 hover:bg-purple-600 transition">New Treatment</button>
            </div>
        </div>
    </div>

    {{-- Bottom row --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mt-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border shadow-sm p-6">
            <h2 class="font-semibold text-lg">Revenue Trend</h2>
            <div class="mt-4">
                <canvas id="revenueChart" height="110"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl border shadow-sm p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-lg flex items-center gap-2">
                    <span class="text-amber-600">⚠️</span>
                    Low Stock Alerts
                </h2>
                <a href="#" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">View All</a>
            </div>

            <div class="mt-4 space-y-3">
                @php
                    $stocks = [
                        ['name' => 'Composite Resin Kit', 'desc' => '8 kits remaining'],
                        ['name' => 'Disposable Gloves (Box)', 'desc' => '15 boxes remaining'],
                        ['name' => 'Local Anesthetic', 'desc' => '12 vials remaining'],
                    ];
                @endphp

                @foreach($stocks as $s)
                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-4 flex items-center justify-between">
                        <div>
                            <div class="font-semibold">{{ $s['name'] }}</div>
                            <div class="text-sm text-slate-500">{{ $s['desc'] }}</div>
                        </div>
                        <span class="text-xs font-semibold px-3 py-1 rounded-lg bg-amber-100 text-amber-700 border border-amber-200">Low</span>
                    </div>
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
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                data: [45000, 52000, 49000, 61000, 56000, 68000],
                tension: 0.35,
                fill: true,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    ticks: {
                        callback: (v) => '₱' + (v/1000) + 'k'
                    }
                }
            }
        }
    });
</script>
@endpush