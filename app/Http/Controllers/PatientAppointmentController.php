<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentChangeRequest;
use App\Models\Service;
use App\Models\User;
use App\Notifications\PatientPortalAlert;
use App\Services\AppointmentRequestNotifier;
use App\Services\AppointmentScheduler;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class PatientAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $patient = $request->user()->patient;
        $status = $request->query('status', 'upcoming');
        $appointments = $patient->appointments()
            ->with(['dentist:id,name', 'serviceItems', 'changeRequests' => fn ($query) => $query->latest()])
            ->when($status === 'pending', fn ($query) => $query->where('status', 'pending'))
            ->when($status === 'completed', fn ($query) => $query->where('status', 'completed'))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->when($status === 'upcoming', fn ($query) => $query->whereIn('status', ['pending', 'confirmed']))
            ->orderByRaw("CASE status WHEN 'confirmed' THEN 0 WHEN 'pending' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->latest('appointment_date')
            ->paginate(8)
            ->withQueryString();

        return view('patient.appointments.index', compact('appointments', 'status'));
    }

    public function create(Request $request)
    {
        return view('patient.appointments.create', [
            'patient' => $request->user()->patient,
            'services' => Service::cached(),
            'leadMinutes' => 120,
            'horizonDays' => 90,
        ]);
    }

    public function dates(Request $request, AppointmentScheduler $scheduler)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
        ]);

        $duration = (int) Service::whereKey($data['service_ids'])->sum('duration_minutes');

        return response()->json([
            'days' => $scheduler->dateSummary($data['start_date'], $duration),
        ]);
    }

    public function slots(Request $request, AppointmentScheduler $scheduler)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
        ]);

        $services = Service::whereKey($data['service_ids'])->get();

        return response()->json([
            'duration' => (int) $services->sum('duration_minutes'),
            'estimated_total' => (float) $services->sum('price'),
            'slots' => $scheduler->publicSlots($data['date'], (int) $services->sum('duration_minutes')),
        ]);
    }

    public function store(Request $request, AppointmentScheduler $scheduler, AppointmentRequestNotifier $notifier, TransactionalEmailDispatcher $emails)
    {
        $data = $request->validate([
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'requested_start_at' => ['required', 'date'],
            'concern' => ['nullable', 'string', 'max:3000'],
        ]);

        $user = $request->user();
        $patient = $user->patient;
        $services = Service::whereKey($data['service_ids'])->get()->keyBy('id');

        if ($services->count() !== count($data['service_ids'])) {
            throw ValidationException::withMessages(['service_ids' => 'One selected service is no longer available.']);
        }

        $appointment = DB::transaction(function () use ($data, $services, $scheduler, $patient, $user): Appointment {
            $duration = (int) $services->sum('duration_minutes');
            $requestedStart = $scheduler->parseLocal($data['requested_start_at']);
            $scheduler->holdPublicRange($requestedStart, $duration);
            $localStart = $requestedStart->setTimezone(AppointmentScheduler::TIMEZONE);

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'requested_by_user_id' => $user->id,
                'full_name' => $patient->name,
                'contact_number' => $patient->mobile ?: '',
                'email' => $patient->email,
                'appointment_date' => $localStart->toDateString(),
                'appointment_time' => $localStart->format('g:i A'),
                'preferred_date' => $localStart->toDateString(),
                'preferred_time_window' => (int) $localStart->format('H') < 12 ? 'morning' : 'afternoon',
                'requested_start_at' => $requestedStart,
                'requested_end_at' => $requestedStart->addMinutes($duration),
                'duration_minutes' => $duration,
                'scheduling_mode' => 'exact',
                'service' => $services->first()->name,
                'concern' => $data['concern'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($data['service_ids'] as $order => $id) {
                $service = $services[$id];
                $appointment->serviceItems()->create([
                    'service_id' => $service->id,
                    'name_snapshot' => $service->name,
                    'price_snapshot' => $service->price,
                    'duration_minutes_snapshot' => $service->duration_minutes,
                    'display_order' => $order,
                ]);
            }

            return $appointment->load('serviceItems');
        });

        DB::afterCommit(function () use ($appointment, $user, $notifier, $emails): void {
            $user->notify(new PatientPortalAlert(
                'appointment_request_received',
                'Appointment request received',
                'Your appointment request is pending clinic confirmation.',
                route('patient.appointments.show', $appointment, false),
                $appointment->id,
            ));
            if ($appointment->email) {
                $emails->dispatchOnce(
                    'booking_received',
                    $appointment->email,
                    $appointment,
                    (string) Uuid::uuid5(Uuid::NAMESPACE_URL, "patient-booking-received/{$appointment->id}"),
                );
            }
            $notifier->notify($appointment, $user);
        });

        return redirect()->route('patient.appointments.show', $appointment)->with('status', 'Appointment request submitted.');
    }

    public function show(Request $request, Appointment $appointment)
    {
        $appointment = $request->user()->patient->appointments()
            ->with(['dentist:id,name', 'serviceItems', 'changeRequests' => fn ($query) => $query->latest()])
            ->whereKey($appointment->id)
            ->firstOrFail();

        return view('patient.appointments.show', compact('appointment'));
    }

    public function withdraw(Request $request, Appointment $appointment)
    {
        $appointment = $request->user()->patient->appointments()->whereKey($appointment->id)->firstOrFail();

        if ($appointment->status !== 'pending') {
            throw ValidationException::withMessages(['appointment' => 'Only pending appointments can be withdrawn directly.']);
        }

        $appointment->forceFill([
            'status' => 'cancelled',
            'cancellation_reason' => 'Withdrawn by patient.',
            'cancelled_at' => now(),
        ])->save();

        return redirect()->route('patient.appointments.index')->with('status', 'Pending appointment withdrawn.');
    }

    public function requestChange(Request $request, Appointment $appointment, AppointmentScheduler $scheduler)
    {
        $appointment = $request->user()->patient->appointments()
            ->with('serviceItems')
            ->whereKey($appointment->id)
            ->firstOrFail();

        if ($appointment->status !== 'confirmed') {
            throw ValidationException::withMessages(['appointment' => 'Only confirmed appointments can be changed by request.']);
        }

        if ($appointment->changeRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['appointment' => 'This appointment already has a pending request.']);
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['cancel', 'reschedule'])],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'proposed_start_at' => ['nullable', 'required_if:type,reschedule', 'date'],
        ]);

        $proposedStart = filled($data['proposed_start_at'] ?? null) ? $scheduler->parseLocal($data['proposed_start_at']) : null;
        $duration = $appointment->total_duration_minutes;

        if ($proposedStart) {
            $scheduler->holdPublicRange($proposedStart, $duration, $appointment->id);
        }

        $change = AppointmentChangeRequest::create([
            'appointment_id' => $appointment->id,
            'patient_user_id' => $request->user()->id,
            'patient_id' => $appointment->patient_id,
            'type' => $data['type'],
            'reason' => $data['reason'],
            'original_start_at' => $appointment->scheduled_start_at,
            'original_end_at' => $appointment->scheduled_end_at,
            'proposed_start_at' => $proposedStart,
            'proposed_end_at' => $proposedStart?->addMinutes($duration),
            'status' => 'pending',
        ]);

        User::whereIn('role', ['admin', 'receptionist'])->where('status', 'active')->get()
            ->each(fn (User $staff) => $staff->notify(new PatientPortalAlert(
                'appointment_change_requested',
                'Appointment change requested',
                "{$appointment->full_name} requested a {$change->type}.",
                route('appointment-change-requests.index', [], false),
                $change->id,
                "user/{$staff->id}",
            )));

        return redirect()->route('patient.appointments.show', $appointment)->with('status', ucfirst($change->type).' request submitted.');
    }
}
