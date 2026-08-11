<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        // Every keystroke in the auto-filter re-runs this as a fresh request against a remote
        // (Supabase/Tokyo) DB — ~6 round trips per load. Cache the assembled page per unique
        // filter/page combo for a short window; Patient::bumpIndexCacheVersion() (fired by
        // Patient/DentalRecord/Invoice writes) invalidates all of them instantly on any change.
        $cacheKey = sprintf(
            'patients:index:v%d:%s',
            Patient::indexCacheVersion(),
            md5($request->getQueryString() ?? '')
        );

        $patients = Cache::remember($cacheKey, 20, fn () => Patient::query()
            ->when($request->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")
            ))
            ->when($request->status && $request->status !== 'All Status', fn ($q) => $q->where('status', strtolower($request->status)))
            ->when($request->gender && $request->gender !== 'All Gender', fn ($q) => $q->where('gender', $request->gender))
            ->withMax('dentalRecords as last_visit', 'treatment_date')
            ->latest()
            ->paginate(10)
            ->withQueryString());

        return view('patients.index', [
            'patients' => $patients,
            'viewPatientId' => $request->integer('view') ?: null,
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

    public function detailFrame(Patient $patient)
    {
        $patient->load([
            'dentalRecords' => fn ($query) => $query->latest('treatment_date'),
            'dentalRecords.dentist:id,name',
            'invoices' => fn ($query) => $query->latest('invoice_date'),
        ])->loadMax('dentalRecords as last_visit', 'treatment_date');

        return view('patients.detail-frame', ['patient' => $patient]);
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

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

    public function update(Request $request, Patient $patient)
    {
        $data = $this->validated($request);

        $patient->update($data);

        return $this->respond($request, redirect()->route('patients.index', ['view' => $patient->id])->with('status', 'Patient updated successfully.'));
    }

    public function deactivate(Patient $patient)
    {
        $patient->update(['status' => $patient->status === 'active' ? 'inactive' : 'active']);

        return redirect()->route('patients.edit', $patient)->with('status', 'Patient status updated.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
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
            'allergies' => ['nullable', 'string', 'max:255'],
            'medications' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }
}
