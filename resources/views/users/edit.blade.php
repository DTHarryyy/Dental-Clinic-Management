@extends('layouts.app')
@section('page_title', 'Edit Staff')
@section('content')
<div class="mb-6 flex items-center justify-between gap-4"><div><h1 class="page-title">Edit Staff Member</h1><p class="page-subtitle">Role and status changes take effect on the next request.</p></div><a href="{{ route('users.index') }}" class="text-sm font-semibold text-slate-600">← Back</a></div>
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@include('users._form-dialog', ['staffUser' => $staffUser])</div>
@endsection
