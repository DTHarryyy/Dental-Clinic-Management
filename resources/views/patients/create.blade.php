@extends('layouts.app')
@section('page_title', 'Add Patient')
@section('content')
<div class="mb-6 flex items-center justify-between gap-4"><div><h1 class="page-title">Add New Patient</h1><p class="page-subtitle">Create the patient information allowed for your role.</p></div><a href="{{ route('patients.index') }}" class="text-sm font-semibold text-slate-600">← Back</a></div>
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">@include('patients._form-dialog', ['patient' => null])</div>
@endsection
