@extends('layouts.app')
@section('page_title', 'Clinic Reports')

@php
    $tabLabels = ['overview' => 'Overview', 'financial' => 'Financial', 'appointments' => 'Appointments', 'patients-services' => 'Patients & Services', 'dentists' => 'Dentists'];
    $query = ['period' => $range->period, 'from' => $range->from->toDateString(), 'to' => $range->to->toDateString()];
    $sectionKey = $activeTab === 'patients-services' ? 'patientsServices' : $activeTab;
    $section = $report[$sectionKey];
    $charts = $section['charts'] ?? [];
@endphp

@section('content')
<div class="report-page min-w-0" data-report-page>
    <header class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="page-title">Clinic performance</h1>
            <p class="mt-1 text-sm text-slate-500">Validated financial, operational, patient, service, and dentist analytics.</p>
        </div>
        <div class="no-print grid grid-cols-2 gap-2 sm:flex">
            <button type="button" onclick="window.print()" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500">
                <i class="fa-solid fa-print mr-1" aria-hidden="true"></i> Print section
            </button>
            <a href="{{ route('reports.export.pdf', $query) }}" data-turbo="false" class="flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500">
                <i class="fa-solid fa-file-pdf mr-1" aria-hidden="true"></i> Full PDF
            </a>
        </div>
    </header>

    @if ($errors->has('date_range'))
        <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $errors->first('date_range') }}</div>
    @endif

    <form method="GET" class="no-print mb-5 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4" x-data="{ period: @js($range->period) }">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div class="flex flex-wrap gap-2" aria-label="Report date presets">
            @foreach (['today' => 'Today', '7d' => '7 days', '30d' => '30 days', 'this_month' => 'This month', 'this_quarter' => 'This quarter', 'this_year' => 'This year', 'custom' => 'Custom'] as $value => $label)
                <button name="period" value="{{ $value }}" type="submit" @click="period = '{{ $value }}'" class="min-h-11 rounded-xl px-3 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 {{ $range->period === $value ? 'bg-emerald-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div x-show="period === 'custom'" x-cloak class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <label class="block text-xs font-semibold text-slate-600">From
                <input class="filter-control mt-1 min-h-11 w-full" type="date" name="from" value="{{ $range->from->toDateString() }}" max="{{ now('Asia/Manila')->toDateString() }}">
            </label>
            <label class="block text-xs font-semibold text-slate-600">To
                <input class="filter-control mt-1 min-h-11 w-full" type="date" name="to" value="{{ $range->to->toDateString() }}" max="{{ now('Asia/Manila')->toDateString() }}">
            </label>
            <button name="period" value="custom" class="min-h-11 rounded-xl bg-slate-800 px-5 text-sm font-semibold text-white hover:bg-slate-900">Apply range</button>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
            <span><strong class="text-slate-700">Selected:</strong> {{ $range->label() }}</span>
            <span>Compared with {{ $range->previousLabel() }} · {{ ucfirst($report['range']['bucket']) }} trend buckets</span>
        </div>
    </form>

    <nav class="no-print mb-5 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm" aria-label="Report sections">
        <div class="grid grid-cols-2 gap-1 sm:flex">
            @foreach ($tabLabels as $tab => $label)
                <a href="{{ route('reports', array_merge($query, ['tab' => $tab])) }}" @class(['flex min-h-11 items-center justify-center rounded-xl px-3 text-center text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-500 sm:px-4', 'col-span-2 sm:col-auto' => $loop->last, 'bg-emerald-600 text-white' => $activeTab === $tab, 'text-slate-600 hover:bg-slate-100' => $activeTab !== $tab]) aria-current="{{ $activeTab === $tab ? 'page' : 'false' }}">{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    <div class="print-heading mb-5 hidden border-b border-slate-300 pb-4">
        <h1 class="text-2xl font-bold">{{ $clinic->clinic_name ?: config('app.name') }} — {{ $tabLabels[$activeTab] }}</h1>
        <p>{{ $range->label() }} · Compared with {{ $range->previousLabel() }} · Generated {{ $generatedAt->format('M j, Y g:i A') }} PHT</p>
    </div>

    <section aria-labelledby="report-section-heading">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="report-section-heading" class="text-xl font-bold text-slate-900">{{ $tabLabels[$activeTab] }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $range->label() }}</p>
            </div>
            <details class="no-print relative">
                <summary class="flex min-h-11 cursor-pointer list-none items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-file-csv mr-1.5" aria-hidden="true"></i> CSV downloads</summary>
                <div class="fixed inset-x-4 z-20 mt-2 max-h-[min(28rem,70vh)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl sm:absolute sm:inset-x-auto sm:right-0 sm:w-56">
                    @foreach (['payments', 'invoices', 'appointments', 'patients', 'services', 'dentists'] as $dataset)
                        <a data-turbo="false" class="block min-h-11 rounded-lg px-3 py-3 text-sm font-medium capitalize text-slate-700 hover:bg-emerald-50 hover:text-emerald-700" href="{{ route('reports.export.csv', array_merge(['dataset' => $dataset], $query)) }}">{{ $dataset }}</a>
                    @endforeach
                </div>
            </details>
        </div>

        @include('reports.partials.kpis', ['kpis' => $section['kpis']])

        @include('reports.partials.insights', ['insights' => $section['insights'] ?? []])

        @include('reports.partials.chart-grid', compact('charts', 'activeTab'))

        @include('reports.partials.section-content', ['tab' => $activeTab, 'section' => $section])
    </section>
</div>

<script type="application/json" data-analytics-charts>@json($charts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .report-chart-shell { height: var(--chart-height, 18rem); min-width: 0; position: relative; }
    .report-progress { width: var(--progress, 0%); }
    @media (max-width: 420px) { .report-chart-shell { max-height: 16rem; } }
    @media print {
        .no-print, nav, aside, header:not(.report-page header), [data-bottom-nav] { display: none !important; }
        .print-heading { display: block !important; }
        .report-page { color: #0f172a; }
        .report-chart-shell { height: 220px; break-inside: avoid; }
        section, article, table { break-inside: avoid; }
        main { padding: 0 !important; }
        body { background: white !important; }
    }
</style>
@endpush
