<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        $records = DentalRecord::query()
            ->select(['id', 'patient_id', 'dentist_id', 'treatment_date', 'procedure', 'clinical_notes'])
            ->with(['patient:id,first_name,last_name', 'dentist:id,name'])
            ->when($request->search, fn ($q) => $q->where('procedure', 'like', "%{$request->search}%")
                ->orWhereHas('patient', fn ($q2) => $q2
                    ->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")))
            ->when($request->service && $request->service !== 'All Services', fn ($q) => $q->where('procedure', $request->service))
            ->latest('treatment_date')
            ->paginate(10)
            ->withQueryString();

        return view('records.index', [
            'records' => $records,
            'dentists' => $this->assignableDentists($request),
            'services' => Service::cached()->pluck('name'),
        ]);
    }

    public function create(Request $request)
    {
        return view('records.create', [
            'dentists' => $this->assignableDentists($request),
            'services' => Service::cached()->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $isDentist = $request->user()->roleEnum() === Role::Dentist;

        if ($isDentist && ($request->filled('create_invoice') || $request->filled('treatment_fee'))) {
            throw new AuthorizationException('Dentists cannot perform billing actions.');
        }
        if ($isDentist && $request->filled('dentist_id') && (int) $request->input('dentist_id') !== (int) $request->user()->id) {
            throw new AuthorizationException('Dentists may only file records as themselves.');
        }

        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_id' => [
                'nullable',
                'exists:appointments,id',
                Rule::unique('dental_records', 'appointment_id'),
                function ($attr, $value, $fail) use ($request) {
                    if (! $value) {
                        return;
                    }

                    $appointment = Appointment::find($value);
                    if ($appointment && $appointment->status !== 'confirmed') {
                        $fail('Only a confirmed appointment can be completed.');
                    } elseif ($appointment && (int) $appointment->patient_id !== (int) $request->patient_id) {
                        $fail('The selected patient does not match this appointment.');
                    }
                },
            ],
            'treatment_date' => ['required', 'date'],
            'dentist_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'dentist')->where('status', 'active'))],
            'procedure' => ['required', Rule::in(Service::names())],
            'tooth_area' => ['nullable', 'string', 'max:255'],
            'next_appointment_date' => ['nullable', 'date'],
            'clinical_notes' => ['required', 'string'],
            'prescription' => ['nullable', 'string'],
            'treatment_fee' => [$isDentist ? 'prohibited' : 'nullable', 'numeric', 'min:0'],
            'create_invoice' => ['prohibited'],
        ], [
            'procedure.in' => 'That procedure is no longer available. Please pick one from the list.',
            'appointment_id.unique' => 'This appointment already has a treatment record.',
        ]);

        $appointment = filled($data['appointment_id'] ?? null)
            ? Appointment::with('serviceItems')->findOrFail($data['appointment_id'])
            : null;

        if ($isDentist && $appointment && (int) $appointment->dentist_id !== (int) $request->user()->id) {
            throw new AuthorizationException('Dentists may only complete their assigned appointments.');
        }

        $suggestedFee = $this->suggestedFee($appointment, $data['procedure']);
        $data['dentist_id'] = $isDentist
            ? $request->user()->id
            : ($data['dentist_id'] ?? $appointment?->dentist_id);
        $data['treatment_fee'] = $isDentist
            ? $suggestedFee
            : ($data['treatment_fee'] ?? $suggestedFee);

        $record = DB::transaction(function () use ($data) {
            $record = DentalRecord::create([
                ...$data,
            ]);

            // Filing the record is what completes the appointment - both land together or
            // neither does, so a completed appointment always has its record to back it up.
            if ($record->appointment_id) {
                Appointment::whereKey($record->appointment_id)->update(['status' => 'completed']);
            }

            return $record;
        });

        // Completing from the appointments queue sends you back to the queue (the row now links
        // to its record); a standalone walk-in record has nowhere to return to, so show it.
        $redirect = $record->appointment_id
            ? redirect()->route('appointments.index')->with('status', 'Appointment completed and treatment record sent to the billing queue.')
            : redirect()->route('records.show', $record)->with('status', 'Treatment record saved.');

        return $this->respond($request, $redirect);
    }

    public function show(Request $request, DentalRecord $record)
    {
        $record->load(['patient', 'dentist']);
        if ($request->user()->can('viewBilling', $record->patient)) {
            $record->load('invoice');
        } else {
            $record->makeHidden('treatment_fee');
        }

        return view('records.show', ['record' => $record]);
    }

    private function suggestedFee(?Appointment $appointment, string $procedure): float
    {
        if ($appointment?->serviceItems->isNotEmpty()) {
            return (float) $appointment->serviceItems->sum('price_snapshot');
        }

        return (float) (Service::query()->where('name', $procedure)->value('price') ?? 0);
    }

    private function assignableDentists(Request $request)
    {
        return $request->user()->roleEnum() === Role::Dentist
            ? collect([$request->user()])
            : User::cachedDentists();
    }
}
