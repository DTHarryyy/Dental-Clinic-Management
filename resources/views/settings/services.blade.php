@extends('layouts.app')
@section('page_title', 'Settings — Services')

@section('content')
{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Settings</h1>
    <p class="text-slate-500 text-sm mt-0.5">Configure your clinic preferences and system options</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

    {{-- Sub-nav --}}
    <div class="xl:col-span-1">
        @include('settings._nav')
    </div>

    {{-- Content --}}
    <div class="xl:col-span-3">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Card header --}}
            <div class="flex items-center justify-between gap-3 px-6 py-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600">
                        <i class="fa-solid fa-tooth"></i>
                    </div>
                    <div>
                        <h2 class="font-semibold text-base text-slate-800 flex items-center gap-2">
                            Services &amp; Pricing
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">{{ $services->count() }}</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">The catalog used across appointments, records and billing</p>
                    </div>
                </div>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-service-create'))"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm shrink-0">
                    <i class="fa-solid fa-plus"></i> <span class="hidden sm:inline">Add Service</span>
                </button>
            </div>

            {{-- Table --}}
            @if ($services->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="responsive-stack-table services-table w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                                <th class="text-left px-6 py-3 font-semibold">Service</th>
                                <th class="text-right px-6 py-3 font-semibold">Price</th>
                                <th class="text-left px-6 py-3 font-semibold hidden sm:table-cell">Duration</th>
                                <th class="text-left px-6 py-3 font-semibold hidden lg:table-cell">Public</th>
                                <th class="text-right px-6 py-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($services as $svc)
                                <tr class="hover:bg-slate-50 transition group">
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-emerald-50 text-emerald-600">
                                                @if($svc->public_image_path)
                                                    <img src="{{ asset('storage/'.$svc->public_image_path) }}" alt="{{ $svc->name }}" class="h-full w-full object-cover">
                                                @else
                                                    <i class="fa-solid fa-tooth"></i>
                                                @endif
                                            </span>
                                            <span>{{ $svc->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-slate-800 whitespace-nowrap">₱{{ number_format($svc->price, 2) }}</td>
                                    <td class="px-6 py-4 text-slate-500 hidden sm:table-cell">{{ $svc->duration ?: '—' }}</td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $svc->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $svc->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                    onclick='window.dispatchEvent(new CustomEvent("open-service-edit", { detail: @js(["id" => $svc->id, "name" => $svc->name, "price" => $svc->price, "duration_minutes" => $svc->duration_minutes, "public_description" => $svc->public_description, "public_image_path" => $svc->public_image_path, "public_sort_order" => $svc->public_sort_order, "show_public_price" => $svc->show_public_price, "is_active" => $svc->is_active]) }))'
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 transition"
                                                    aria-label="Edit {{ $svc->name }}">
                                                <i class="fa-solid fa-pen text-[11px]"></i> Edit
                                            </button>
                                            <button type="button"
                                                    onclick='window.dispatchEvent(new CustomEvent("open-service-delete", { detail: @js(["id" => $svc->id, "name" => $svc->name]) }))'
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 transition"
                                                    aria-label="Delete {{ $svc->name }}">
                                                <i class="fa-solid fa-trash text-[11px]"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                {{-- Empty state --}}
                <div class="px-6 py-14 text-center">
                    <div class="h-14 w-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
                        <i class="fa-solid fa-tooth text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-slate-800">No services yet</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Add the treatments your clinic offers. They’ll appear as options when booking appointments and creating records.</p>
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-service-create'))"
                            class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                        <i class="fa-solid fa-plus"></i> Add your first service
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@include('settings._service-dialog')
@include('settings._delete-dialog')
@endsection
