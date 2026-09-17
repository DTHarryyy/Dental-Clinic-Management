<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentChangeRequest;
use App\Notifications\PatientPortalAlert;
use App\Services\AppointmentScheduler;
use App\Services\SecurityAudit;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use Throwable;

class AppointmentChangeRequestController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : 'pending';

        $requests = AppointmentChangeRequest::with(['appointment.serviceItems', 'appointment.dentist:id,name', 'patient', 'patientUser', 'resolver'])
            ->where('status', $status)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('staff.appointment-change-requests.index', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function resolve(Request $request, AppointmentChangeRequest $changeRequest, AppointmentScheduler $scheduler, TransactionalEmailDispatcher $emails, SecurityAudit $audit)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'resolution_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $changeRequest, $request, $scheduler, $audit): void {
            $changeRequest->load(['appointment.serviceItems', 'patientUser']);
            $appointment = $changeRequest->appointment()->lockForUpdate()->firstOrFail();

            if ($changeRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This request has already been resolved.']);
            }

            if ($data['decision'] === 'approve') {
                if ($changeRequest->type === 'cancel') {
                    $appointment->forceFill([
                        'status' => 'cancelled',
                        'cancellation_reason' => $changeRequest->reason,
                        'cancelled_at' => now(),
                    ])->save();
                } else {
                    $start = $changeRequest->proposed_start_at;
                    if (! $start) {
                        throw ValidationException::withMessages(['request' => 'This reschedule request has no proposed time.']);
                    }
                    $scheduler->holdPublicRange($start->toImmutable(), $appointment->total_duration_minutes, $appointment->id);
                    $end = $start->copy()->addMinutes($appointment->total_duration_minutes);

                    if ($appointment->dentist_id) {
                        $dentistConflict = Appointment::query()
                            ->where('dentist_id', $appointment->dentist_id)
                            ->where('status', 'confirmed')
                            ->whereKeyNot($appointment->id)
                            ->where('scheduled_start_at', '<', $end)
                            ->where('scheduled_end_at', '>', $start)
                            ->exists();

                        if ($dentistConflict) {
                            throw ValidationException::withMessages(['request' => 'The assigned dentist is no longer available for that time.']);
                        }
                    }

                    $appointment->forceFill([
                        'requested_start_at' => $start,
                        'requested_end_at' => $end,
                        'scheduled_start_at' => $start,
                        'scheduled_end_at' => $end,
                        'appointment_date' => $start->copy()->setTimezone(AppointmentScheduler::TIMEZONE)->toDateString(),
                        'appointment_time' => $start->copy()->setTimezone(AppointmentScheduler::TIMEZONE)->format('g:i A'),
                    ])->save();
                }
            }

            $changeRequest->update([
                'status' => $data['decision'] === 'approve' ? 'approved' : 'rejected',
                'resolved_by_user_id' => $request->user()->id,
                'resolution_note' => $data['resolution_note'],
                'resolved_at' => now(),
            ]);

            $audit->record('appointment_change_request.resolved', 'allowed', $request->user(), target: $changeRequest);
        });

        $changeRequest->patientUser->notify(new PatientPortalAlert(
            'appointment_change_decision',
            'Appointment request '.$changeRequest->status,
            "Your {$changeRequest->type} request was {$changeRequest->status}.",
            route('patient.appointments.show', $changeRequest->appointment, false),
            $changeRequest->id,
            "user/{$changeRequest->patientUser->id}",
        ));

        if ($changeRequest->appointment->email) {
            try {
                $emails->dispatchOnce(
                    'appointment_change_decision',
                    $changeRequest->appointment->email,
                    $changeRequest->appointment,
                    (string) Uuid::uuid5(Uuid::NAMESPACE_URL, "appointment-change-decision/{$changeRequest->id}"),
                );
            } catch (Throwable $exception) {
                Log::error('Unable to send appointment change decision email.', ['change_request_id' => $changeRequest->id, 'exception' => $exception]);
            }
        }

        return back()->with('status', 'Appointment change request resolved.');
    }
}
