<?php

namespace App\Http\Controllers;

use App\Models\AppointmentChangeRequest;
use App\Notifications\PatientPortalAlert;
use App\Services\AppointmentScheduler;
use App\Services\SecurityAudit;
use App\Services\TransactionalEmailDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class AppointmentChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = AppointmentChangeRequest::with(['appointment.serviceItems', 'appointment.dentist:id,name', 'patient', 'patientUser', 'resolver'])
            ->where('status', $request->query('status', 'pending'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('staff.appointment-change-requests.index', [
            'requests' => $requests,
            'status' => $request->query('status', 'pending'),
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
                    $appointment->forceFill([
                        'requested_start_at' => $start,
                        'requested_end_at' => $start->copy()->addMinutes($appointment->total_duration_minutes),
                        'scheduled_start_at' => $start,
                        'scheduled_end_at' => $start->copy()->addMinutes($appointment->total_duration_minutes),
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
            $emails->dispatchOnce(
                'appointment_change_decision',
                $changeRequest->appointment->email,
                $changeRequest->appointment,
                (string) Uuid::uuid5(Uuid::NAMESPACE_URL, "appointment-change-decision/{$changeRequest->id}"),
            );
        }

        return back()->with('status', 'Appointment change request resolved.');
    }
}
