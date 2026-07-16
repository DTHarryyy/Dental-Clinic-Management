@php
    $dash = fn ($v) => filled($v) ? $v : '—';
@endphp

<div class="flex min-h-0 flex-1 flex-col">
    <div class="min-h-0 flex-1 overflow-y-auto">

        {{-- Identity banner --}}
        <div class="flex items-center gap-4 border-b border-slate-100 bg-slate-50/70 px-6 py-5">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-xl font-bold text-emerald-700">
                {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <h3 class="truncate text-lg font-bold text-slate-800">{{ $patient->name }}</h3>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <span class="font-medium">Patient #{{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}</span>
                    <span class="text-slate-300">·</span>
                    <span>{{ $patient->age ? $patient->age . ' yrs' : '—' }}</span>
                    @if ($patient->status === 'active')
                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active</span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inactive</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detail sections --}}
        <div class="divide-y divide-slate-100 px-6">

            <section class="py-6">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i class="fa-solid fa-user text-sm"></i></span>
                    <h4 class="text-sm font-semibold text-slate-800">Personal Information</h4>
                </div>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <x-field label="Date of Birth">{{ optional($patient->dob)->format('F j, Y') ?? '—' }}</x-field>
                    <x-field label="Gender">{{ $dash($patient->gender) }}</x-field>
                    <x-field label="Civil Status">{{ $dash($patient->civil_status) }}</x-field>
                    <x-field label="Occupation">{{ $dash($patient->occupation) }}</x-field>
                </dl>
            </section>

            <section class="py-6">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><i class="fa-solid fa-address-book text-sm"></i></span>
                    <h4 class="text-sm font-semibold text-slate-800">Contact Information</h4>
                </div>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <x-field label="Mobile Number">{{ $dash($patient->mobile) }}</x-field>
                    <x-field label="Email Address">{{ $dash($patient->email) }}</x-field>
                    <x-field label="Home Address" class="sm:col-span-2">{{ $dash($patient->address) }}</x-field>
                    <x-field label="Emergency Contact">{{ $dash($patient->emergency_contact_name) }}</x-field>
                    <x-field label="Emergency Number">{{ $dash($patient->emergency_contact_number) }}</x-field>
                </dl>
            </section>

            <section class="py-6">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-500"><i class="fa-solid fa-notes-medical text-sm"></i></span>
                    <h4 class="text-sm font-semibold text-slate-800">Medical History</h4>
                </div>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <x-field label="Known Allergies">{{ $patient->allergies ?: 'None' }}</x-field>
                    <x-field label="Current Medications">{{ $patient->medications ?: 'None' }}</x-field>
                    <x-field label="Medical Conditions" class="sm:col-span-2">
                        <div class="flex flex-wrap gap-1.5">
                            @forelse (($patient->conditions ?? []) as $c)
                                <span class="rounded-lg border border-red-100 bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">{{ $c }}</span>
                            @empty
                                <span class="text-slate-500">None reported</span>
                            @endforelse
                        </div>
                    </x-field>
                    @if (filled($patient->notes))
                        <x-field label="Additional Notes" class="sm:col-span-2">{{ $patient->notes }}</x-field>
                    @endif
                </dl>
            </section>
        </div>
    </div>

    {{-- Pinned footer --}}
    <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-white px-6 py-4">
        <div class="text-xs text-slate-400">
            Registered {{ $patient->created_at->format('M j, Y') }}
            @if (! empty($patient->last_visit))
                <span class="mx-1 text-slate-300">·</span> Last visit {{ \Illuminate\Support\Carbon::parse($patient->last_visit)->format('M j, Y') }}
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('patients.show', $patient) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-up-right-from-square text-xs"></i> Full Profile
            </a>
            <button type="button" x-on:click="open = false; $dispatch('open-dialog', { id: 'patient-edit-{{ $patient->id }}' })" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
                <i class="fa-solid fa-pen text-xs"></i> Edit
            </button>
        </div>
    </div>
</div>
