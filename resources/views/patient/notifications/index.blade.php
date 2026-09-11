@extends('layouts.patient')
@section('page_title', 'Notifications')

@section('content')
<div class="page-header"><div><h1 class="page-title">Notifications</h1><p class="page-subtitle">Appointment, billing, and treatment updates from the clinic.</p></div>@if(auth()->user()->unreadNotifications()->count())<form action="{{ route('patient.notifications.read-all') }}" method="POST">@csrf @method('PATCH')<button class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Mark all read</button></form>@endif</div>
<div class="responsive-card divide-y divide-slate-100 overflow-hidden">
    @forelse($notifications as $notification)
        <a href="{{ route('patient.notifications.open', $notification) }}" class="block p-4 hover:bg-slate-50 sm:p-5 {{ $notification->read_at ? 'bg-white' : 'bg-emerald-50/50' }}">
            <div class="flex gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700' }}"><i class="fa-regular fa-bell"></i></span><div class="min-w-0 flex-1"><p class="font-semibold text-slate-800">{{ $notification->data['title'] ?? 'Clinic notification' }}</p><p class="mt-1 text-sm text-slate-500">{{ $notification->data['message'] ?? 'Open this update for details.' }}</p><p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p></div></div>
        </a>
    @empty
        <div class="p-10 text-center text-sm text-slate-500">No notifications yet.</div>
    @endforelse
</div>
@if($notifications->hasPages())<div class="mt-5">{{ $notifications->links() }}</div>@endif
@endsection
