@extends('layouts.public')
@section('page_title', $title.' — Aquilizan Dental Clinic')

@section('content')
<nav class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-4xl items-center justify-between px-3 py-3 sm:px-5">
        <a href="{{ route('home') }}" class="flex items-center gap-3 text-sm font-bold text-slate-800">
            <img src="{{ asset('images/aquilizan-logo.png') }}" alt="Aquilizan Dental Clinic logo" class="h-10 w-10 rounded-2xl object-contain">
            DentalCare
        </a>
        <a href="{{ route('login') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign in</a>
    </div>
</nav>
<main class="mx-auto max-w-4xl px-3 py-8 sm:px-5">
    <article class="responsive-card responsive-card-padding">
        <h1 class="page-title">{{ $title }}</h1>
        @if($updated)<p class="page-subtitle">Updated {{ $updated->format('M j, Y') }}</p>@endif
        <div class="mt-6 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $body ?: 'This content is being prepared by the clinic administrator.' }}</div>
    </article>
</main>
@endsection
