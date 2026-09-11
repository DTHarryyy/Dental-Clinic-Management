@extends('layouts.patient')
@section('page_title', 'Treatment Summary')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">Treatment Summary</h1><p class="page-subtitle">{{ $record->treatment_date->format('M j, Y') }} · {{ $record->procedure }}</p></div>
    <a href="{{ route('patient.treatments.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-2"></i>Back</a>
</div>
<div class="grid gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
    <aside class="responsive-card responsive-card-padding">
        <dl class="space-y-4 text-sm">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Procedure</dt><dd class="mt-1 font-semibold text-slate-800">{{ $record->procedure }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tooth area</dt><dd class="mt-1 text-slate-700">{{ $record->tooth_area ?: 'Not specified' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dentist</dt><dd class="mt-1 text-slate-700">{{ $record->dentist->name ?? 'Clinic dentist' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Published</dt><dd class="mt-1 text-slate-700">{{ $record->published_at->setTimezone('Asia/Manila')->format('M j, Y') }}</dd></div>
        </dl>
    </aside>
    <section class="space-y-5">
        <div class="responsive-card responsive-card-padding"><h2 class="font-bold text-slate-800">Summary</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $record->patient_summary }}</p></div>
        <div class="responsive-card responsive-card-padding"><h2 class="font-bold text-slate-800">Aftercare</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $record->aftercare_instructions ?: 'No additional aftercare instructions were published.' }}</p></div>
        @if($record->prescription)<div class="responsive-card responsive-card-padding"><h2 class="font-bold text-slate-800">Prescription</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $record->prescription }}</p></div>@endif
        @if($record->next_appointment_date)<div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"><i class="fa-solid fa-calendar-days mr-1.5"></i>Suggested next appointment: {{ $record->next_appointment_date->format('M j, Y') }}</div>@endif
    </section>
</div>
@endsection
