<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = Appointment::query()
            ->with(['patient', 'dentist', 'dentalRecord'])
            ->when($request->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('full_name', 'like', "%{$request->search}%")
                ->orWhere('service', 'like', "%{$request->search}%")
            ))
            ->when($request->date, fn ($q) => $q->whereDate('appointment_date', $request->date))
            ->when($request->status && $request->status !== 'All Status', fn ($q) => $q->where('status', strtolower($request->status)))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(9)
            ->withQueryString();

        $summaryRow = Appointment::selectRaw(
            "COUNT(CASE WHEN appointment_date = ? THEN 1 END) as today,
             COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
             COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
             COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed",
            [today()->toDateString()]
        )->first();

        $summary = [
            'today' => $summaryRow->today,
            'confirmed' => $summaryRow->confirmed,
            'pending' => $summaryRow->pending,
            'completed' => $summaryRow->completed,
        ];

        return view('appointments.index', [
            'appointments' => $appointments,
            'summary' => $summary,
            'patients' => Patient::dropdown(),
            'dentists' => User::cachedDentists(),
            'services' => Service::cached()->pluck('name'),
            // Seeds the treatment fee when completing an appointment. Keyed by name because
            // appointments snapshot the service name rather than referencing the catalog row.
            'servicePrices' => Service::cached()->pluck('price', 'name'),
        ]);
    }

    public function create()
    {
        return view('appointments.create', [
            'patients' => Patient::dropdown(),
            'dentists' => User::cachedDentists(),
            'services' => Service::cached()->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'string'],
            'dentist_id' => ['nullable', 'exists:users,id'],
            'service' => ['required', Rule::in(Service::names())],
            'concern' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,confirmed'],
        ], [
            'service.in' => 'That service is no longer available. Please pick one from the list.',
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        $data['full_name'] = $patient->name;
        $data['contact_number'] = $patient->mobile;
        $data['email'] = $patient->email;
        $data['status'] = $data['status'] ?? 'pending';

        Appointment::create($data);

        return $this->respond($request, redirect()->route('appointments.index')->with('status', 'Appointment booked successfully.'));
    }

    /**
     * The appointment lifecycle. Previously only the view decided which moves were possible,
     * so a hand-made request could jump any appointment straight to any status — including
     * cancelling one that had already been completed, leaving its treatment record attached
     * to a cancelled visit. Every reachable state keeps a way out: nothing is a dead end.
     */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled', 'completed'],
        'completed' => ['confirmed'], // reopen — refused below once a record exists
        'cancelled' => ['pending'],   // reopen
    ];

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => ['required', 'in:pending,confirmed,cancelled,completed']]);

        $to = $request->status;
        $from = $appointment->status;

        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'appointment' => "An appointment that is {$from} cannot be marked {$to}.",
            ]);
        }

        // Reopening is for closing a mistake, not for undoing treatment that actually happened.
        if ($from === 'completed' && $appointment->dentalRecord()->exists()) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment has a treatment record, so it can no longer be reopened.',
            ]);
        }

        $appointment->update(['status' => $to]);

        return $this->respond($request, back()->with('status', self::statusMessage($to)));
    }

    private static function statusMessage(string $status): string
    {
        return match ($status) {
            'confirmed' => 'Appointment confirmed.',
            'cancelled' => 'Appointment cancelled.',
            'completed' => 'Appointment marked complete.',
            'pending' => 'Appointment reopened — it is pending again.',
        };
    }
}
