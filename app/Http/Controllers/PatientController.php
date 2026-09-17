<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $canViewClinicalDirectory = $request->user()->hasPermission(Permission::PatientsViewClinical);

        // Every keystroke in the auto-filter re-runs this as a fresh request against a remote
        // (Supabase/Tokyo) DB — ~6 round trips per load. Cache the assembled page per unique
        // filter/page combo; Patient::bumpIndexCacheVersion() (fired by Patient/DentalRecord/
        // Invoice writes) invalidates all of them instantly on any change, so the TTL below
        // only bounds staleness if a bump were ever missed — it doesn't drive correctness.
        $cacheKey = sprintf(
            'patients:index:v%d:clinical-%d:%s',
            Patient::indexCacheVersion(),
            (int) $canViewClinicalDirectory,
            md5($request->getQueryString() ?? '')
        );

        $patients = Cache::remember($cacheKey, 600, fn () => Patient::query()
            ->when(! $canViewClinicalDirectory, fn ($query) => $query->select(Patient::BASIC_COLUMNS))
            ->when($request->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")
            ))
            ->when($request->status && $request->status !== 'All Status', fn ($q) => $q->where('status', strtolower($request->status)))
            ->when($request->gender && $request->gender !== 'All Gender', fn ($q) => $q->where('gender', $request->gender))
            ->when($canViewClinicalDirectory, fn ($query) => $query->withMax('dentalRecords as last_visit', 'treatment_date'))
            ->latest()
            ->paginate(10)
            ->withQueryString());

        return view('patients.index', [
            'patients' => $patients,
            'viewPatientId' => $request->integer('view') ?: null,
            'canViewClinicalDirectory' => $canViewClinicalDirectory,
        ]);
    }

    public function lookup(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $patients = Patient::query()
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search): void {
                $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $query->where(function ($query) use ($operator, $search): void {
                    $query->where('first_name', $operator, "%{$search}%")
                        ->orWhere('last_name', $operator, "%{$search}%")
                        ->orWhere('email', $operator, "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get()
            ->map(fn (Patient $patient) => [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
            ]);

        return response()->json(['data' => $patients]);
    }

    public function detailFrame(Request $request, Patient $patient)
    {
        $canViewClinical = $request->user()->can('viewClinical', $patient);
        $canViewBilling = $request->user()->can('viewBilling', $patient);

        if ($canViewClinical) {
            $patient->load([
                'dentalRecords' => fn ($query) => $query
                    ->select(['id', 'patient_id', 'dentist_id', 'treatment_date', 'procedure', 'clinical_notes'])
                    ->latest('treatment_date'),
                'dentalRecords.dentist:id,name',
            ])->loadMax('dentalRecords as last_visit', 'treatment_date');
        }

        if ($canViewBilling) {
            $patient->load(['invoices' => fn ($query) => $query->latest('invoice_date')]);
        }

        return view('patients.detail-frame', compact('patient', 'canViewClinical', 'canViewBilling'));
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $this->rejectUnauthorizedCreateFields($request);
        $data = $request->validate($this->creationRules($request));
        $data['status'] = $request->user()->hasPermission(Permission::PatientsSetInitialStatus)
            ? ($data['status'] ?? 'active')
            : 'active';

        $patient = Patient::create($data);

        return $this->respond($request, redirect()->route('patients.index', ['view' => $patient->id])->with('status', 'Patient added successfully.'));
    }

    public function show(Patient $patient)
    {
        return redirect()->route('patients.index', ['view' => $patient->id]);
    }

    public function edit(Patient $patient)
    {
        return view('patients.edit', ['patient' => $patient]);
    }

    public function updateDemographics(Request $request, Patient $patient)
    {
        $this->rejectUnauthorizedFieldGroups($request, [
            ['permission' => Permission::PatientsUpdateClinical, 'fields' => array_keys($this->clinicalRules())],
            ['permission' => Permission::PatientsChangeStatus, 'fields' => ['status']],
        ]);
        $data = $request->validate($this->demographicRules());

        $patient->update($data);

        return $this->respond($request, redirect()->route('patients.index', ['view' => $patient->id])->with('status', 'Patient details updated successfully.'));
    }

    public function updateClinical(Request $request, Patient $patient)
    {
        $this->rejectUnauthorizedFieldGroups($request, [
            ['permission' => Permission::PatientsUpdateDemographics, 'fields' => array_keys($this->demographicRules())],
            ['permission' => Permission::PatientsChangeStatus, 'fields' => ['status']],
        ]);
        $patient->update($request->validate($this->clinicalRules()));

        return $this->respond($request, redirect()->route('patients.index', ['view' => $patient->id])->with('status', 'Medical history updated successfully.'));
    }

    public function updateStatus(Request $request, Patient $patient)
    {
        $this->rejectUnauthorizedFieldGroups($request, [
            ['permission' => Permission::PatientsUpdateClinical, 'fields' => array_keys($this->clinicalRules())],
        ]);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);
        $patient->update($data);

        return $this->respond($request, back()->with('status', 'Patient status updated.'));
    }

    private function demographicRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string'],
            'civil_status' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    private function clinicalRules(): array
    {
        return [
            'allergies' => ['nullable', 'string', 'max:255'],
            'medications' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'conditions.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function creationRules(Request $request): array
    {
        $demographicRules = $this->demographicRules();
        $extendedFields = ['civil_status', 'occupation', 'emergency_contact_name', 'emergency_contact_number'];
        $rules = collect($demographicRules)->except($extendedFields)->all();

        if ($request->user()->hasPermission(Permission::PatientsCreateExtendedDemographics)) {
            $rules = [...$rules, ...collect($demographicRules)->only($extendedFields)->all()];
        }

        if ($request->user()->hasPermission(Permission::PatientsUpdateClinical)) {
            $rules = [...$rules, ...$this->clinicalRules()];
        }

        if ($request->user()->hasPermission(Permission::PatientsSetInitialStatus)) {
            $rules['status'] = ['nullable', 'in:active,inactive'];
        }

        return $rules;
    }

    private function rejectUnauthorizedCreateFields(Request $request): void
    {
        if ($request->hasAny(['civil_status', 'occupation', 'emergency_contact_name', 'emergency_contact_number'])
            && ! $request->user()->hasPermission(Permission::PatientsCreateExtendedDemographics)) {
            Gate::authorize(Permission::PatientsCreateExtendedDemographics->value);
        }

        if ($request->hasAny(['allergies', 'medications', 'conditions', 'notes'])
            && ! $request->user()->hasPermission(Permission::PatientsUpdateClinical)) {
            Gate::authorize(Permission::PatientsUpdateClinical->value);
        }

        if ($request->has('status') && ! $request->user()->hasPermission(Permission::PatientsSetInitialStatus)) {
            Gate::authorize(Permission::PatientsSetInitialStatus->value);
        }
    }

    /** @param list<array{permission: Permission, fields: list<string>}> $groups */
    private function rejectUnauthorizedFieldGroups(Request $request, array $groups): void
    {
        foreach ($groups as $group) {
            $permission = $group['permission'];

            if ($request->hasAny($group['fields']) && ! $request->user()->hasPermission($permission)) {
                Gate::authorize($permission->value);
            }
        }
    }
}
