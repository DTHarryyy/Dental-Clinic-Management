@extends('layouts.patient')
@section('page_title', 'Treatment Summaries')

@section('content')
<div class="page-header"><div><h1 class="page-title">Treatment Summaries</h1><p class="page-subtitle">Only dentist-published patient summaries appear here.</p></div></div>
<div class="grid gap-4 lg:grid-cols-2">
    @forelse($records as $record)
        <a href="{{ route('patient.treatments.show', $record) }}" class="responsive-card responsive-card-padding block hover:border-emerald-200">
            <div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-slate-800">{{ $record->procedure }}</h2><p class="mt-1 text-sm text-slate-500">{{ $record->treatment_date->format('M j, Y') }} @if($record->dentist)· {{ $record->dentist->name }}@endif</p></div><i class="fa-solid fa-chevron-right text-slate-300"></i></div>
            @if($record->patient_summary)<p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $record->patient_summary }}</p>@endif
        </a>
    @empty
        <div class="responsive-card responsive-card-padding text-center text-sm text-slate-500">No treatment summaries have been published yet.</div>
    @endforelse
</div>
@if($records->hasPages())<div class="mt-5">{{ $records->links() }}</div>@endif
@endsection
