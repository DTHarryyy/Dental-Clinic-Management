@extends('layouts.app')
@section('page_title', 'Settings - Team Profiles')

@section('content')
<div class="mb-6"><h1 class="page-title">Settings</h1><p class="page-subtitle">Manage dentists shown on the public website.</p></div>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
    <div class="xl:col-span-1">@include('settings._nav')</div>
    <div class="xl:col-span-3 space-y-5">
        <div class="responsive-card responsive-card-padding">
            <h2 class="font-bold text-slate-800">Add team profile</h2>
            <form action="{{ route('settings.team.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <select name="user_id" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm"><option value="">No linked staff user</option>@foreach($dentists as $dentist)<option value="{{ $dentist->id }}">{{ $dentist->name }}</option>@endforeach</select>
                <input name="name" required placeholder="Display name" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <input name="title" placeholder="Title" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <input name="specialties" placeholder="Specialties" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <input type="number" name="display_order" value="0" min="0" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm">
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                <textarea name="biography" rows="3" placeholder="Short biography" class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></textarea>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-emerald-600"> Publish on website
                </label>
                <div class="sm:col-span-2"><button class="primary-action"><i class="fa-solid fa-plus"></i> Add profile</button></div>
            </form>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($profiles as $profile)
                <div class="responsive-card responsive-card-padding">
                    <form action="{{ route('settings.team.update', $profile) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="flex items-start gap-3"><div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-emerald-100 text-emerald-700">@if($profile->photo_path)<img src="{{ asset('storage/'.$profile->photo_path) }}" alt="{{ $profile->name }}" class="h-full w-full object-cover">@else<i class="fa-solid fa-user-doctor"></i>@endif</div><div class="min-w-0 flex-1"><input name="name" value="{{ $profile->name }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold"><p class="mt-1 text-xs text-slate-500">{{ $profile->user->name ?? 'No linked user' }}</p></div></div>
                        <div class="grid gap-3 sm:grid-cols-2"><input name="title" value="{{ $profile->title }}" placeholder="Title" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"><input name="specialties" value="{{ $profile->specialties }}" placeholder="Specialties" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"><input type="number" name="display_order" value="{{ $profile->display_order }}" min="0" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"><select name="user_id" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"><option value="">No linked staff user</option>@foreach($dentists as $dentist)<option value="{{ $dentist->id }}" @selected($profile->user_id === $dentist->id)>{{ $dentist->name }}</option>@endforeach</select></div>
                        <textarea name="biography" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">{{ $profile->biography }}</textarea>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" @checked($profile->is_published) class="rounded border-slate-300 text-emerald-600"> Published
                        </label>
                        @if($profile->photo_path)<label class="flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label>@endif
                        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                            <button class="primary-action"><i class="fa-solid fa-check"></i> Save</button>
                        </div>
                    </form>
                    <form action="{{ route('settings.team.destroy', $profile) }}" method="POST" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex min-h-11 items-center rounded-xl bg-red-50 px-4 text-sm font-semibold text-red-600">Remove</button>
                    </form>
                </div>
            @empty
                <div class="responsive-card responsive-card-padding text-center text-sm text-slate-500">No team profiles yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
