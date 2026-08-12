@extends('layouts.app')
@section('page_title', 'Edit Patient')
@section('content')
<div class="mb-6 flex items-center justify-between gap-4"><div><h1 class="page-title">Edit {{ $patient->name }}</h1><p class="page-subtitle">Only fields authorized for your role are available.</p></div><a href="{{ route('patients.show', $patient) }}" class="text-sm font-semibold text-slate-600">← Back</a></div>
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">@include('patients._form-dialog', ['patient' => $patient])</div>
@endsection
