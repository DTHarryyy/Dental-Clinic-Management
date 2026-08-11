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

            <section class="py-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600"><i class="fa-solid fa-stethoscope text-sm"></i></span>
                        <h4 class="text-sm font-semibold text-slate-800">Treatment History</h4>
                    </div>
                    <a href="{{ route('records.create') }}" class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-600">+ Add Record</a>
                </div>
                <div class="space-y-3">
                    @forelse ($patient->dentalRecords as $r)
                        <div class="flex items-start gap-4 rounded-xl border border-slate-100 p-4 transition hover:bg-slate-50">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-lg text-teal-600"><i class="fa-solid fa-stethoscope"></i></div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-slate-800">{{ $r->procedure }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $r->treatment_date->format('M j, Y') }} · {{ $r->dentist->name ?? '—' }}</div>
                                <div class="mt-1 text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($r->clinical_notes, 120) }}</div>
                            </div>
                            <a href="{{ route('records.show', $r) }}" class="shrink-0 text-xs font-semibold text-emerald-600 hover:text-emerald-700">View →</a>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No treatment records yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="py-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><i class="fa-solid fa-file-invoice-dollar text-sm"></i></span>
                        <h4 class="text-sm font-semibold text-slate-800">Billing History</h4>
                    </div>
                    <a href="{{ route('billing.create') }}" class="rounded-lg bg-violet-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-violet-600">+ Invoice</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                <th class="pb-2 text-left font-semibold">Date</th>
                                <th class="pb-2 text-left font-semibold">Invoice</th>
                                <th class="pb-2 text-left font-semibold">Amount</th>
                                <th class="pb-2 text-left font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($patient->invoices as $b)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="py-3 text-slate-500">{{ $b->invoice_date->format('M j, Y') }}</td>
                                    <td class="py-3 font-medium text-slate-700">{{ $b->invoice_number }}</td>
                                    <td class="py-3 font-semibold text-slate-800">₱{{ number_format($b->total, 2) }}</td>
                                    <td class="py-3">
                                        <span class="rounded-lg px-2 py-0.5 text-xs font-semibold {{ $b->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                            {{ ucfirst($b->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-slate-400">No invoices yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
            <a href="{{ route('appointments.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600">
                <i class="fa-solid fa-calendar-plus text-xs"></i> Book Appointment
            </a>
            <button type="button" x-on:click="open = false; $dispatch('open-dialog', { id: 'patient-edit-{{ $patient->id }}' })" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-pen text-xs"></i> Edit
            </button>
        </div>
    </div>
</div>
