<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::query()
            ->when($request->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")
            ))
            ->when($request->status && $request->status !== 'All Status', fn ($q) => $q->where('status', strtolower($request->status)))
            ->when($request->gender && $request->gender !== 'All Gender', fn ($q) => $q->where('gender', $request->gender))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('patients.index', ['patients' => $patients]);
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $patient = Patient::create($data);

        return $this->respond($request, redirect()->route('patients.show', $patient)->with('status', 'Patient added successfully.'));
    }

    public function show(Patient $patient)
    {
        $patient->load(['dentalRecords' => fn ($q) => $q->latest('treatment_date'), 'invoices' => fn ($q) => $q->latest('invoice_date')]);

        return view('patients.show', ['patient' => $patient]);
    }

    public function edit(Patient $patient)
    {
        return view('patients.edit', ['patient' => $patient]);
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $this->validated($request);

        $patient->update($data);

        return $this->respond($request, redirect()->route('patients.show', $patient)->with('status', 'Patient updated successfully.'));
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
