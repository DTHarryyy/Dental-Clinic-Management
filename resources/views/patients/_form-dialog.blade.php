@php
    $isEdit = isset($patient) && $patient;
    $canDemographics = ! $isEdit || auth()->user()->can('updateDemographics', $patient);
    $canClinical = auth()->user()->hasPermission(\App\Enums\Permission::PatientsUpdateClinical);
    $canExtendedDemographics = $isEdit || auth()->user()->hasPermission(\App\Enums\Permission::PatientsCreateExtendedDemographics);
    $canStatus = $isEdit
        ? auth()->user()->hasPermission(\App\Enums\Permission::PatientsChangeStatus)
        : auth()->user()->hasPermission(\App\Enums\Permission::PatientsSetInitialStatus);
@endphp

<div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
    @if (! $isEdit)
        <form action="{{ route('patients.store') }}" method="POST" data-ajax-form data-loading-text="Adding..." class="space-y-6">
            @csrf
            <div data-error-summary class="hidden"></div>
            @include('patients._demographic-fields', ['patient' => null, 'showExtended' => $canExtendedDemographics])
            @if ($canClinical)
                @include('patients._clinical-fields', ['patient' => null])
            @endif
            @if ($canStatus)
                <label class="block text-sm font-medium text-slate-700">Status
                    <select name="status" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                        <option value="active">Active</option><option value="inactive">Inactive</option>
                    </select>
                </label>
            @endif
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <button type="button" x-on:click="open = false" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white">Save Patient</button>
            </div>
        </form>
    @else
        <div class="space-y-6">
            @if ($canDemographics)
                <form action="{{ route('patients.demographics.update', $patient) }}" method="POST" data-ajax-form data-loading-text="Saving..." class="space-y-5">
                    @csrf @method('PATCH')
                    <div data-error-summary class="hidden"></div>
                    @include('patients._demographic-fields', ['patient' => $patient])
                    <div class="flex justify-end"><button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white">Save Demographics</button></div>
                </form>
            @endif

            @if ($canClinical)
                <form action="{{ route('patients.clinical.update', $patient) }}" method="POST" data-ajax-form data-loading-text="Saving..." class="space-y-5 border-t border-slate-100 pt-6">
                    @csrf @method('PATCH')
                    <div data-error-summary class="hidden"></div>
                    @include('patients._clinical-fields', ['patient' => $patient])
                    <div class="flex justify-end"><button type="submit" class="rounded-xl bg-teal-500 px-5 py-2.5 text-sm font-semibold text-white">Save Medical History</button></div>
                </form>
            @endif

            @if ($canStatus)
                <form action="{{ route('patients.status.update', $patient) }}" method="POST" data-ajax-form data-loading-text="Updating..." class="flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-6">
                    @csrf @method('PATCH')
                    <label class="text-sm font-medium text-slate-700">Patient status
                        <select name="status" class="mt-1.5 block rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                            <option value="active" @selected($patient->status === 'active')>Active</option>
                            <option value="inactive" @selected($patient->status === 'inactive')>Inactive</option>
                        </select>
                    </label>
                    <button type="submit" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700">Update Status</button>
                </form>
            @endif
        </div>
    @endif
</div>
