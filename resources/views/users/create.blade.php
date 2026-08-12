@extends('layouts.app')
@section('page_title', 'Add Staff')
@section('content')
<div class="mb-6 flex items-center justify-between gap-4"><div><h1 class="page-title">Add Staff Member</h1><p class="page-subtitle">Assign one of the clinic’s enforced roles.</p></div><a href="{{ route('users.index') }}" class="text-sm font-semibold text-slate-600">← Back</a></div>
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@include('users._form-dialog', ['staffUser' => null])</div>
@endsection
