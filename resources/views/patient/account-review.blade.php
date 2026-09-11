@extends('layouts.patient')
@section('page_title', 'Account Review')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="responsive-card responsive-card-padding text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-xl text-amber-600"><i class="fa-solid fa-user-clock"></i></div>
        <h1 class="mt-4 text-2xl font-bold text-slate-800">Patient record under review</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Your email matched more than one record or an inactive record. A receptionist will link the correct patient record before portal data is shown.</p>
        @if($request)
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-semibold text-slate-800">{{ ucfirst($request->status) }}</span></div>
                <div class="mt-2 flex justify-between"><span class="text-slate-500">Candidate records</span><span class="font-semibold text-slate-800">{{ $request->candidate_count }}</span></div>
            </div>
        @endif
    </div>
</div>
@endsection
