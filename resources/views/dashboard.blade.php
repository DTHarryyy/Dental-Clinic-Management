@extends('layouts.app')
@section('page_title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    $role = $user->role;
    $isDentist = $user->roleEnum() === \App\Enums\Role::Dentist;
    $periods = [
        'today' => 'Today',
        '7d' => '7 Days',
        '30d' => '30 Days',
        'this_month' => 'This Month',
    ];
    $kpiColors = [
        ['icon' => 'bg-emerald-50 text-emerald-600', 'accent' => 'group-hover:border-emerald-200'],
        ['icon' => 'bg-blue-50 text-blue-600', 'accent' => 'group-hover:border-blue-200'],
        ['icon' => 'bg-violet-50 text-violet-600', 'accent' => 'group-hover:border-violet-200'],
        ['icon' => 'bg-amber-50 text-amber-600', 'accent' => 'group-hover:border-amber-200'],
    ];
    $attentionTones = [
        'amber' => 'bg-amber-50 text-amber-700',
        'red' => 'bg-red-50 text-red-700',
        'blue' => 'bg-blue-50 text-blue-700',
        'violet' => 'bg-violet-50 text-violet-700',
    ];
    $statusColors = [
        'confirmed' => 'bg-emerald-100 text-emerald-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'completed' => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
    $quickActions = collect([
        ['label' => 'Add patient', 'icon' => 'fa-user-plus', 'url' => route('patients.create'), 'tone' => 'bg-emerald-500 hover:bg-emerald-600', 'show' => $user->can('create', \App\Models\Patient::class)],
        ['label' => 'Book appointment', 'icon' => 'fa-calendar-plus', 'url' => route('appointments.create'), 'tone' => 'bg-blue-500 hover:bg-blue-600', 'show' => $user->can('create', \App\Models\Appointment::class)],
        ['label' => 'Create invoice', 'icon' => 'fa-file-invoice-dollar', 'url' => route('billing.create'), 'tone' => 'bg-violet-500 hover:bg-violet-600', 'show' => $user->can('create', \App\Models\Invoice::class)],
        ['label' => 'Add record', 'icon' => 'fa-notes-medical', 'url' => route('records.create'), 'tone' => 'bg-teal-500 hover:bg-teal-600', 'show' => $user->can('create', \App\Models\DentalRecord::class)],
        ['label' => 'Full reports', 'icon' => 'fa-chart-column', 'url' => route('reports'), 'tone' => 'bg-slate-700 hover:bg-slate-800', 'show' => $user->hasPermission(\App\Enums\Permission::ReportsView)],
    ])->where('show', true)->values()->all();
@endphp

<div class="page-header">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="page-title">Dashboard</h1>
            <span class="rounded-full bg-slate-200/70 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-600">{{ $role }}</span>
        </div>
        <p class="page-subtitle">Welcome back, {{ auth()->user()->name }}. Here’s the clinic view for {{ $range['label'] }}.</p>
    </div>
    @if ($user->hasPermission(\App\Enums\Permission::ReportsView))
        <a href="{{ route('reports') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
            <i class="fa-solid fa-chart-column" aria-hidden="true"></i> Full reports
        </a>
    @endif
</div>

<section aria-labelledby="dashboard-range-title" class="responsive-card mb-5 p-3 sm:p-4" x-data="{ custom: @js($range['period'] === 'custom' || $errors->any()) }">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 id="dashboard-range-title" class="text-sm font-bold text-slate-800">Analytics period</h2>
            <p class="mt-0.5 text-xs text-slate-500">Period metrics compare with the immediately preceding period.</p>
        </div>
        <div class="flex flex-wrap gap-2" aria-label="Analytics period presets">
            @foreach ($periods as $value => $label)
                <a href="{{ route('dashboard', ['period' => $value]) }}"
                   class="inline-flex min-h-11 items-center rounded-xl border px-3.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 {{ $range['period'] === $value ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700' }}"
                   @if($range['period'] === $value) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
            <button type="button" x-on:click="custom = true; $nextTick(() => $refs.from.focus())"
                    class="inline-flex min-h-11 items-center rounded-xl border px-3.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300"
                    x-bind:class="custom ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'"
                    x-bind:aria-pressed="custom.toString()">Custom</button>
        </div>
    </div>

    <form method="GET" x-show="custom" x-cloak class="mt-4 border-t border-slate-100 pt-4" aria-label="Custom analytics date range">
        <input type="hidden" name="period" value="custom">
        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
            <label class="block text-xs font-semibold text-slate-600">From
                <input x-ref="from" type="date" name="from" value="{{ old('from', $range['from']) }}" required class="filter-control mt-1 sm:w-full">
            </label>
            <label class="block text-xs font-semibold text-slate-600">To
                <input type="date" name="to" value="{{ old('to', $range['to']) }}" required class="filter-control mt-1 sm:w-full">
            </label>
            <button type="submit" class="primary-action w-full sm:w-auto">Apply range</button>
        </div>
    </form>
    @if ($errors->has('from') || $errors->has('to') || $errors->has('period'))
        <p class="mt-3 text-sm font-medium text-red-600" role="alert">{{ $errors->first('from') ?: ($errors->first('to') ?: $errors->first('period')) }}</p>
    @endif
</section>

<section aria-label="Key performance indicators" class="grid grid-cols-1 gap-3 min-[380px]:grid-cols-2 xl:grid-cols-4 xl:gap-4">
    @foreach ($kpis as $index => $kpi)
        @php $color = $kpiColors[$index % count($kpiColors)]; @endphp
        <a href="{{ $kpi['url'] }}" class="group min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 sm:p-5 {{ $color['accent'] }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                    <p class="break-content mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $kpi['value'] }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-lg {{ $color['icon'] }}"><i class="fa-solid {{ $kpi['icon'] }}" aria-hidden="true"></i></span>
            </div>
            @php
                $direction = $kpi['comparison']['direction'];
                $comparisonColor = $direction === 'up' ? 'text-emerald-700' : ($direction === 'down' ? 'text-red-700' : 'text-slate-500');
                $comparisonIcon = $direction === 'up' ? 'fa-arrow-trend-up' : ($direction === 'down' ? 'fa-arrow-trend-down' : 'fa-minus');
            @endphp
            <p class="mt-3 flex items-start gap-1.5 text-xs font-medium {{ $comparisonColor }}"><i class="fa-solid {{ $comparisonIcon }} mt-0.5" aria-hidden="true"></i><span>{{ $kpi['comparison']['text'] }}</span></p>
        </a>
    @endforeach
</section>

<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
    <section aria-labelledby="primary-chart-title" class="order-4 responsive-card responsive-card-padding xl:order-none xl:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h2 id="primary-chart-title" class="font-bold text-slate-800">{{ $charts['primary']['title'] }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $charts['primary']['subtitle'] }}</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selected period</span>
        </div>
        <p class="sr-only" data-chart-summary="primary">{{ $charts['primary']['summary'] }}</p>
        <div class="dashboard-chart-shell mt-4"><canvas data-dashboard-chart="primary" role="img" aria-labelledby="primary-chart-title" aria-describedby="primary-chart-summary"></canvas></div>
        <p id="primary-chart-summary" class="mt-3 text-xs text-slate-500">{{ $charts['primary']['summary'] }}</p>
    </section>

    <section aria-labelledby="attention-title" class="order-1 responsive-card responsive-card-padding xl:order-none">
        <div class="flex items-center justify-between gap-3">
            <h2 id="attention-title" class="font-bold text-slate-800">{{ $attentionTitle }}</h2>
            <span class="text-xs font-medium text-slate-400">{{ count($attention) }} signals</span>
        </div>
        <div class="mt-4 space-y-2.5">
            @foreach ($attention as $item)
                <a href="{{ $item['url'] }}" class="flex min-h-16 items-center gap-3 rounded-xl border border-slate-100 p-3 transition hover:border-slate-200 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $attentionTones[$item['tone']] }}"><i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-500">{{ $item['label'] }}</span>
                        <span class="break-content block text-base font-bold text-slate-800">{{ $item['value'] }}</span>
                        <span class="block text-[11px] leading-4 text-slate-400">{{ $item['meta'] }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-right text-xs text-slate-300" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="schedule-title" class="order-3 responsive-card responsive-card-padding xl:order-none xl:col-span-2">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 id="schedule-title" class="font-bold text-slate-800">Today’s schedule</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $isDentist ? 'Your assigned appointments' : 'Clinic-wide appointments' }} · Asia/Manila</p>
            </div>
            <a href="{{ route('appointments.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-emerald-700 hover:text-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">View all <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i></a>
        </div>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($todaysAppointments as $appointment)
                @php
                    $time = $appointment->scheduled_start_at ?? $appointment->requested_start_at;
                    $timeLabel = $time ? $time->setTimezone('Asia/Manila')->format('g:i A') : ucfirst($appointment->preferred_time_window ?? 'Time pending');
                @endphp
                <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-2 py-3 sm:grid-cols-[5.5rem_minmax(0,1fr)_auto] sm:items-center">
                    <span class="row-span-2 inline-flex w-fit rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600 sm:row-span-1">{{ $timeLabel }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-800" title="{{ $appointment->full_name }}">{{ $appointment->full_name }}</p>
                        <p class="break-content mt-0.5 text-xs text-slate-500">{{ $appointment->service_names }}@if(! $isDentist && $appointment->dentist_name) · Dr. {{ $appointment->dentist_name }}@endif</p>
                    </div>
                    <span class="col-start-2 w-fit rounded-lg px-2.5 py-1 text-xs font-semibold sm:col-start-3 {{ $statusColors[$appointment->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($appointment->status) }}</span>
                </div>
            @empty
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i class="fa-regular fa-calendar-check text-xl" aria-hidden="true"></i></span>
                    <p class="mt-3 text-sm font-semibold text-slate-600">No appointments scheduled today</p>
                    <p class="mt-1 text-xs text-slate-400">Newly scheduled appointments will appear here.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="quick-actions-title" class="order-2 responsive-card responsive-card-padding xl:order-none">
        <h2 id="quick-actions-title" class="font-bold text-slate-800">Quick actions</h2>
        <p class="mt-0.5 text-xs text-slate-500">Shortcuts available to your role.</p>
        <div class="mt-4 grid grid-cols-2 gap-3">
            @foreach ($quickActions as $action)
                <a href="{{ $action['url'] }}" @if($action['external'] ?? false) target="_blank" rel="noopener" @endif
                   class="inline-flex min-h-20 min-w-0 flex-col items-center justify-center gap-2 rounded-xl px-2 py-3 text-center text-xs font-bold text-white shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $action['tone'] }} {{ count($quickActions) % 2 === 1 && $loop->last ? 'col-span-2' : '' }}">
                    <i class="fa-solid {{ $action['icon'] }} text-lg" aria-hidden="true"></i><span>{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="status-chart-title" class="order-5 responsive-card responsive-card-padding xl:order-none">
        <h2 id="status-chart-title" class="font-bold text-slate-800">{{ $charts['status']['title'] }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ $charts['status']['subtitle'] }}</p>
        <div class="dashboard-chart-shell dashboard-chart-shell-compact mt-4"><canvas data-dashboard-chart="status" role="img" aria-labelledby="status-chart-title" aria-describedby="status-chart-summary"></canvas></div>
        <p id="status-chart-summary" class="mt-3 text-xs leading-5 text-slate-500">{{ $charts['status']['summary'] }}</p>
    </section>

    <section aria-labelledby="breakdown-chart-title" class="order-6 responsive-card responsive-card-padding xl:order-none xl:col-span-2">
        <div>
            <h2 id="breakdown-chart-title" class="font-bold text-slate-800">{{ $charts['breakdown']['title'] }}</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ $charts['breakdown']['subtitle'] }}</p>
        </div>
        @if (count($charts['breakdown']['rows']))
            <div class="mt-4 grid gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(15rem,1fr)] lg:items-center">
                <div class="dashboard-chart-shell dashboard-chart-shell-compact"><canvas data-dashboard-chart="breakdown" role="img" aria-labelledby="breakdown-chart-title" aria-describedby="breakdown-chart-summary"></canvas></div>
                <div class="divide-y divide-slate-100 rounded-xl border border-slate-100" aria-label="{{ $charts['breakdown']['title'] }} details">
                    @foreach ($charts['breakdown']['rows'] as $row)
                        <div class="flex items-center justify-between gap-3 px-3 py-2.5 text-xs">
                            <span class="min-w-0 break-words font-medium text-slate-600">{{ $row['label'] }}</span>
                            <span class="shrink-0 text-right"><strong class="block text-slate-800">{{ $row['sessions'] }} session{{ $row['sessions'] === 1 ? '' : 's' }}</strong>@if($row['fees_display'])<span class="text-[11px] text-slate-400">{{ $row['fees_display'] }}</span>@endif</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-4 rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-400">No service activity in this period.</div>
        @endif
        <p id="breakdown-chart-summary" class="sr-only">{{ $charts['breakdown']['summary'] }}</p>
    </section>
</div>

<script type="application/json" data-dashboard-charts>@json($charts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection
