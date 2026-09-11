<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAccountLinkRequest;
use App\Notifications\PatientPortalAlert;
use App\Services\PatientAccountLinker;
use App\Services\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatientAccountLinkRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = PatientAccountLinkRequest::with(['user', 'selectedPatient', 'resolver'])
            ->where('status', $request->query('status', 'pending'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('staff.patient-accounts.index', [
            'requests' => $requests,
            'patients' => Patient::where('status', 'active')->orderBy('first_name')->limit(200)->get(Patient::BASIC_COLUMNS),
            'status' => $request->query('status', 'pending'),
        ]);
    }

    public function resolve(Request $request, PatientAccountLinkRequest $linkRequest, SecurityAudit $audit)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'patient_id' => ['nullable', 'required_if:decision,approve', Rule::exists('patients', 'id')->where('status', 'active')],
            'note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $linkRequest, $request, $audit): void {
            $linkRequest->load('user');
            $linkRequest->update([
                'status' => $data['decision'] === 'approve' ? 'resolved' : 'rejected',
                'selected_patient_id' => $data['decision'] === 'approve' ? $data['patient_id'] : null,
                'resolved_by_user_id' => $request->user()->id,
                'note' => $data['note'],
                'resolved_at' => now(),
            ]);

            if ($data['decision'] === 'approve') {
                $linkRequest->user->forceFill(['patient_id' => $data['patient_id']])->save();
            }

            $audit->record('patient_account_link.resolved', 'allowed', $request->user(), target: $linkRequest);
        });

        $linkRequest->user->notify(new PatientPortalAlert(
            'account_link_resolved',
            $data['decision'] === 'approve' ? 'Patient record linked' : 'Patient record review updated',
            $data['decision'] === 'approve'
                ? 'Your patient portal is ready.'
                : 'The clinic could not link your account yet. Please contact the front desk.',
            route($data['decision'] === 'approve' ? 'patient.dashboard' : 'patient.account-review', absolute: false),
            $linkRequest->id,
            "user/{$linkRequest->user->id}",
        ));

        return back()->with('status', 'Account link request resolved.');
    }

    public function reviewStatus(Request $request)
    {
        return view('patient.account-review', [
            'request' => PatientAccountLinkRequest::where('user_id', $request->user()->id)->latest()->first(),
        ]);
    }
}
