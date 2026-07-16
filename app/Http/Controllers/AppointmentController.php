<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = Appointment::query()
            ->with(['patient', 'dentist'])
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

        $summary = [
            'today' => Appointment::whereDate('appointment_date', today())->count(),
            'confirmed' => Appointment::where('status', 'confirmed')->count(),
            'pending' => Appointment::where('status', 'pending')->count(),
            'completed' => Appointment::where('status', 'completed')->count(),
        ];

        return view('appointments.index', [
            'appointments' => $appointments,
            'summary' => $summary,
            'patients' => Patient::orderBy('first_name')->get(),
            'dentists' => User::where('role', 'dentist')->orderBy('name')->get(),
            'services' => Service::orderBy('name')->pluck('name'),
        ]);
    }

    public function create()
    {
        return view('appointments.create', [
            'patients' => Patient::orderBy('first_name')->get(),
            'dentists' => User::where('role', 'dentist')->orderBy('name')->get(),
            'services' => Service::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'string'],
            'dentist_id' => ['nullable', 'exists:users,id'],
            'service' => ['required', 'string'],
            'concern' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,confirmed'],
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        $data['full_name'] = $patient->name;
        $data['contact_number'] = $patient->mobile;
        $data['email'] = $patient->email;
        $data['status'] = $data['status'] ?? 'pending';

        Appointment::create($data);

        return $this->respond($request, redirect()->route('appointments.index')->with('status', 'Appointment booked successfully.'));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => ['required', 'in:confirmed,cancelled,completed']]);

        $appointment->update(['status' => $request->status]);

        return back()->with('status', 'Appointment status updated.');
    }
}
