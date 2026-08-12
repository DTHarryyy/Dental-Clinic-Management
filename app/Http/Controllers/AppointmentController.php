<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Jobs\SendAppointmentConfirmationEmail;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentScheduler;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = Appointment::query()
            ->visibleTo($request->user())
            ->select(['id', 'patient_id', 'dentist_id', 'full_name', 'email', 'appointment_date', 'appointment_time', 'preferred_date', 'preferred_time_window', 'requested_start_at', 'requested_end_at', 'scheduling_mode', 'duration_minutes', 'scheduled_start_at', 'scheduled_end_at', 'service', 'status', 'created_at'])
            ->with([
                'patient:id,first_name,last_name',
                'dentist:id,name',
                'dentalRecord:id,appointment_id',
                'serviceItems' => fn ($query) => $query->when(
                    $request->user()->roleEnum() === Role::Dentist,
                    fn ($query) => $query->select(['id', 'appointment_id', 'service_id', 'name_snapshot', 'duration_minutes_snapshot', 'display_order'])
                ),
            ])
            ->when($request->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('full_name', 'like', "%{$request->search}%")
                ->orWhere('service', 'like', "%{$request->search}%")
            ))
            ->when($request->integer('appointment'), fn ($q) => $q->whereKey($request->integer('appointment')))
            ->when($request->date, fn ($q) => $q->whereDate('appointment_date', $request->date))
            ->when($request->status && $request->status !== 'All Status', fn ($q) => $q->where('status', strtolower($request->status)))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->orderBy('created_at')
            ->paginate(9)
            ->withQueryString();

        $summaryRow = Appointment::query()->visibleTo($request->user())->selectRaw(
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
            'dentists' => $this->assignableDentists($request),
            'services' => Service::cached(),
            // Seeds the treatment fee when completing an appointment. Keyed by name because
            // appointments snapshot the service name rather than referencing the catalog row.
            'servicePrices' => Service::cached()->pluck('price', 'name'),
        ]);
    }

    public function create(Request $request)
    {
        return view('appointments.create', [
            'dentists' => $this->assignableDentists($request),
            'services' => Service::cached(),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->roleEnum() === Role::Dentist
            && $request->filled('dentist_id')
            && (int) $request->input('dentist_id') !== (int) $request->user()->id) {
            throw new AuthorizationException('Dentists may only create appointments assigned to themselves.');
        }

        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'preferred_date' => ['required', 'date'],
            'preferred_time_window' => ['required', 'in:morning,afternoon'],
            'requested_start_at' => ['required', 'date'],
            'dentist_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'dentist')->where('status', 'active'))],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')],
            'concern' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,confirmed'],
        ], [
            'service.in' => 'That service is no longer available. Please pick one from the list.',
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        $data['full_name'] = $patient->name;
        $data['contact_number'] = $patient->mobile;
        $data['email'] = $patient->email;
        $data['status'] = 'pending';
        if ($request->user()->roleEnum() === Role::Dentist) {
            $data['dentist_id'] = $request->user()->id;
        }
        $services = Service::whereKey($data['service_ids'])->get()->keyBy('id');
        DB::transaction(function () use ($data, $services) {
            $scheduler = app(AppointmentScheduler::class);
            $duration = (int) $services->sum('duration_minutes');
            $requestedStart = $scheduler->parseLocal($data['requested_start_at']);
            $scheduler->holdPublicRange($requestedStart, $duration);
            $data['preferred_date'] = $requestedStart->setTimezone(AppointmentScheduler::TIMEZONE)->toDateString();
            $data['preferred_time_window'] = (int) $requestedStart->setTimezone(AppointmentScheduler::TIMEZONE)->format('H') < 12 ? 'morning' : 'afternoon';
            $appointment = Appointment::create([...collect($data)->except('service_ids')->all(),
                'appointment_date' => $data['preferred_date'], 'appointment_time' => ucfirst($data['preferred_time_window']),
                'requested_end_at' => $requestedStart->addMinutes($duration), 'duration_minutes' => $duration,
                'scheduling_mode' => 'exact', 'service' => $services->first()->name]);
            foreach ($data['service_ids'] as $order => $id) {
                $service = $services[$id];
                $appointment->serviceItems()->create(['service_id' => $id, 'name_snapshot' => $service->name,
                    'price_snapshot' => $service->price, 'duration_minutes_snapshot' => $service->duration_minutes, 'display_order' => $order]);
            }
        });

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

    public function availability(Request $request, Appointment $appointment, AppointmentScheduler $scheduler)
    {
        if ($request->user()->roleEnum() === Role::Dentist
            && (int) $request->input('dentist_id') !== (int) $request->user()->id) {
            throw new AuthorizationException('Dentists may only inspect availability for themselves.');
        }

        $data = $request->validate(['dentist_id' => ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'dentist')->where('status', 'active'))], 'date' => ['required', 'date'], 'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:480', 'multiple_of:30']]);
        $appointment->load('serviceItems');

        return response()->json(['slots' => $scheduler->availableSlots($appointment, (int) $data['dentist_id'], $data['date'], (int) ($data['duration_minutes'] ?? $appointment->total_duration_minutes)),
            'older_requests' => $scheduler->olderCompetitors($appointment)->map->only(['id', 'full_name', 'created_at']),
            'preferred_date' => $appointment->preferred_date?->toDateString(), 'preferred_window' => $appointment->preferred_time_window]);
    }

    public function updateStatus(Request $request, Appointment $appointment, TransactionalEmailDispatcher $emails, AppointmentScheduler $scheduler)
    {
        $request->validate(['status' => ['required', 'in:pending,confirmed,cancelled,completed']]);

        $to = $request->status;
        $from = $appointment->status;

        if ($request->user()->roleEnum() === Role::Dentist
            && $to === 'confirmed'
            && $request->filled('dentist_id')
            && (int) $request->input('dentist_id') !== (int) $request->user()->id) {
            throw new AuthorizationException('Dentists cannot assign an appointment to another dentist.');
        }

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

        if ($to === 'cancelled') {
            $request->validate([
                'cancellation_reason' => ['required', 'string', 'min:3', 'max:1000'],
                'reschedule_requested' => ['nullable', 'boolean'],
                'reschedule_date' => ['nullable', 'date', 'after_or_equal:today'],
                'reschedule_start_at' => ['nullable', 'date'],
                'reschedule_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:480', 'multiple_of:30'],
                'reschedule_window' => ['nullable', 'in:morning,afternoon'],
            ]);
        }

        if ($to === 'confirmed') {
            $appointment->loadMissing('serviceItems');
            $request->merge([
                'dentist_id' => $request->user()->roleEnum() === Role::Dentist
                    ? $request->user()->id
                    : $request->input('dentist_id', $appointment->dentist_id),
                'scheduled_start_at' => $request->input('scheduled_start_at', $appointment->scheduled_start_at?->toIso8601String()),
                'scheduling_mode' => $request->input('scheduling_mode', 'exact'),
                'duration_minutes' => $request->input('scheduling_mode', 'exact') === 'exact'
                    ? $request->input('duration_minutes', $appointment->total_duration_minutes)
                    : null,
            ]);
            $request->validate([
                'dentist_id' => ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'dentist')->where('status', 'active'))],
                'scheduled_start_at' => ['required', 'date'],
                'scheduling_mode' => ['required', 'in:exact,first_come'],
                'duration_minutes' => ['nullable', 'required_if:scheduling_mode,exact', 'integer', 'min:30', 'max:480', 'multiple_of:30'],
                'session_end_at' => ['nullable', 'required_if:scheduling_mode,first_come', 'date', 'after:scheduled_start_at'],
                'preference_change_acknowledged' => ['sometimes', 'accepted'],
                'priority_override_reason' => ['nullable', 'string', 'max:1000'],
            ]);
        }

        DB::transaction(function () use ($request, $appointment, $from, $to, $scheduler) {
            if ($from === 'pending' && $to === 'confirmed' && ! $appointment->patient_id) {
                $appointment->patient_id = $this->resolvePatient($appointment)->id;
            }

            if ($to === 'confirmed') {
                $appointment->load('serviceItems');
                $start = $scheduler->parseLocal($request->scheduled_start_at);
                $duration = $request->scheduling_mode === 'exact' ? (int) $request->duration_minutes : null;
                if ($request->scheduling_mode === 'exact' && ! $scheduler->preferenceMatches($appointment, $start, $duration) && ! $request->boolean('preference_change_acknowledged')) {
                    throw ValidationException::withMessages(['preference_change_acknowledged' => 'Acknowledge that this schedule is outside the patient preference.']);
                }
                $sessionEnd = filled($request->session_end_at) ? $scheduler->parseLocal($request->session_end_at) : null;
                $scheduler->reserve($appointment, (int) $request->dentist_id, $start, $duration, $request->scheduling_mode, $sessionEnd, $request->priority_override_reason);
            } elseif ($to === 'cancelled') {
                $appointment->load('serviceItems');
                $appointment->forceFill(['status' => 'cancelled', 'cancellation_reason' => $request->cancellation_reason,
                    'cancelled_at' => now()])->save();

                if ($request->boolean('reschedule_requested')) {
                    if (blank($request->reschedule_start_at) && blank($request->reschedule_window)) {
                        throw ValidationException::withMessages(['reschedule_start_at' => 'Choose an exact new time.']);
                    }
                    $legacyTime = $request->reschedule_window === 'afternoon' ? '13:00' : '08:00';
                    $rescheduleStart = $scheduler->parseLocal($request->reschedule_start_at ?: $request->reschedule_date.' '.$legacyTime);
                    $rescheduleDuration = (int) ($request->reschedule_duration_minutes ?: $appointment->total_duration_minutes);
                    $scheduler->holdPublicRange($rescheduleStart, $rescheduleDuration, $appointment->id);
                    $replacement = Appointment::create([
                        'patient_id' => $appointment->patient_id, 'full_name' => $appointment->full_name,
                        'contact_number' => $appointment->contact_number, 'email' => $appointment->email,
                        'preferred_date' => $rescheduleStart->setTimezone(AppointmentScheduler::TIMEZONE)->toDateString(),
                        'preferred_time_window' => (int) $rescheduleStart->setTimezone(AppointmentScheduler::TIMEZONE)->format('H') < 12 ? 'morning' : 'afternoon',
                        'requested_start_at' => $rescheduleStart, 'requested_end_at' => $rescheduleStart->addMinutes($rescheduleDuration),
                        'duration_minutes' => $rescheduleDuration, 'scheduling_mode' => 'exact',
                        'dentist_id' => $request->user()->roleEnum() === Role::Dentist ? $request->user()->id : null,
                        'appointment_date' => $rescheduleStart->setTimezone(AppointmentScheduler::TIMEZONE)->toDateString(),
                        'appointment_time' => $rescheduleStart->setTimezone(AppointmentScheduler::TIMEZONE)->format('g:i A'),
                        'service' => $appointment->service, 'concern' => $appointment->concern, 'status' => 'pending',
                    ]);
                    foreach ($appointment->serviceItems as $item) {
                        $replacement->serviceItems()->create($item->only(['service_id', 'name_snapshot', 'price_snapshot', 'duration_minutes_snapshot', 'display_order']));
                    }
                    $appointment->update(['rescheduled_appointment_id' => $replacement->id]);
                }
            } else {
                $appointment->status = $to;
                $appointment->save();
            }
        });

        if ($from === 'pending' && $to === 'confirmed' && filled($appointment->email)) {
            SendAppointmentConfirmationEmail::dispatch($appointment->id)->afterCommit();
        }

        if ($to === 'cancelled' && filled($appointment->email)) {
            $emails->dispatch('appointment_cancelled', $appointment->email, $appointment);
        }

        return $this->respond($request, back()->with('status', self::statusMessage($to)));
    }

    private function resolvePatient(Appointment $appointment): Patient
    {
        $email = mb_strtolower(trim((string) $appointment->email));
        $phone = preg_replace('/\D+/', '', (string) $appointment->contact_number);

        $patient = $email !== ''
            ? Patient::whereRaw('LOWER(TRIM(email)) = ?', [$email])->oldest('id')->first()
            : null;

        if (! $patient && $phone !== '') {
            $patient = Patient::whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?",
                [$phone]
            )->oldest('id')->first();
        }

        if ($patient) {
            return $patient;
        }

        $nameParts = preg_split('/\s+/', trim($appointment->full_name), 2);

        return Patient::create([
            'first_name' => $nameParts[0] ?: 'Patient',
            'last_name' => $nameParts[1] ?? '',
            'mobile' => filled($appointment->contact_number) ? trim($appointment->contact_number) : null,
            'email' => $email !== '' ? $email : null,
            'status' => 'active',
        ]);
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

    private function assignableDentists(Request $request)
    {
        return $request->user()->roleEnum() === Role::Dentist
            ? collect([$request->user()])
            : User::cachedDentists();
    }
}
