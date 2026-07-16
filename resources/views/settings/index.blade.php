@extends('layouts.app')
@section('page_title', 'Settings')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Settings</h1>
        <p class="text-slate-500 text-sm mt-0.5">Configure your clinic preferences and system options</p>
    </div>
</div>


<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

    {{-- Sidebar nav --}}
    <div class="xl:col-span-1">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3 space-y-0.5 sticky top-20">
            @php
                $sections = [
                    ['id' => 'clinic',   'icon' => 'fa-hospital', 'label' => 'Clinic Info'],
                    ['id' => 'services', 'icon' => 'fa-tooth',    'label' => 'Services'],
                ];
            @endphp
            @foreach ($sections as $i => $sec)
                <a href="#{{ $sec['id'] }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ $i === 0 ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid {{ $sec['icon'] }} w-4 text-center"></i>
                    {{ $sec['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Main content --}}
    <div class="xl:col-span-3 space-y-6">

        {{-- Clinic Info --}}
        <div id="clinic" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-5 pb-4 border-b border-slate-100">
                <div class="h-8 w-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <i class="fa-solid fa-hospital"></i>
                </div>
                <h2 class="font-semibold text-base text-slate-800">Clinic Information</h2>
            </div>
            <form action="{{ route('settings.clinic') }}" method="POST" data-ajax-form data-loading-text="Saving...">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Clinic Name</label>
                        <input type="text" name="clinic_name" value="{{ old('clinic_name', $clinic->clinic_name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                        <input type="tel" name="phone" value="{{ old('phone', $clinic->phone) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $clinic->email) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                        <input type="text" name="address" value="{{ old('address', $clinic->address) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tax ID / TIN</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id', $clinic->tax_id) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Website</label>
                        <input type="url" name="website" value="{{ old('website', $clinic->website) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                    </div>
                </div>
                <button type="submit" class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
                    <i class="fa-solid fa-floppy-disk"></i> Save Clinic Info
                </button>
            </form>
        </div>

        {{-- Services --}}
        <div id="services" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600">
                        <i class="fa-solid fa-tooth"></i>
                    </div>
                    <h2 class="font-semibold text-base text-slate-800">Services & Pricing</h2>
                </div>
            </div>

            <div class="space-y-2">
                @forelse ($services as $svc)
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition group">
                        <form action="{{ route('settings.services.update', $svc) }}" method="POST" class="flex items-center gap-3 flex-1 min-w-0" data-ajax-form data-loading-text="Saving...">
                            @csrf
                            @method('PUT')
                            <div class="flex-1 min-w-0">
                                <input type="text" name="name" value="{{ $svc->name }}"
                                    class="font-medium text-slate-800 text-sm bg-transparent border-none outline-none w-full focus:bg-white focus:border focus:border-emerald-300 focus:ring-0 focus:px-2 rounded-lg transition" />
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <span class="text-slate-400 text-sm">₱</span>
                                <input type="number" name="price" value="{{ $svc->price }}" step="0.01"
                                    class="w-20 text-right font-semibold text-slate-800 text-sm bg-transparent border-none outline-none focus:bg-white focus:border focus:border-emerald-300 focus:ring-0 focus:px-2 rounded-lg transition" />
                            </div>
                            <div class="w-24 shrink-0">
                                <input type="text" name="duration" value="{{ $svc->duration }}" placeholder="duration"
                                    class="w-full text-right text-xs text-slate-500 bg-transparent border-none outline-none focus:bg-white focus:border focus:border-emerald-300 focus:ring-0 focus:px-2 rounded-lg transition" />
                            </div>
                            <button type="submit" class="text-emerald-500 hover:text-emerald-700 transition" title="Save">
                                <i class="fa-solid fa-check text-xs"></i>
                            </button>
                        </form>
                        <form action="{{ route('settings.services.destroy', $svc) }}" method="POST" onsubmit="return confirm('Remove this service?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition" title="Delete">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No services configured yet.</p>
                @endforelse
            </div>

            <form action="{{ route('settings.services.store') }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-2" data-ajax-form data-loading-text="Adding...">
                @csrf
                <input type="text" name="name" placeholder="New service name" class="sm:col-span-2 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" required />
                <input type="number" name="price" placeholder="Price" step="0.01" min="0" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" required />
                <div class="flex gap-2">
                    <input type="text" name="duration" placeholder="Duration" class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200" />
                    <button type="submit" class="px-3 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold shrink-0"><i class="fa-solid fa-plus"></i></button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
