<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PatientDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $patient = $request->user()->patient;
        $now = now();

        $appointments = $patient->appointments()
            ->with(['dentist:id,name', 'serviceItems'])
            ->latest('created_at')
            ->get();

        $nextAppointment = $appointments
            ->where('status', 'confirmed')
            ->filter(fn ($appointment) => $appointment->scheduled_start_at?->gte($now))
            ->sortBy('scheduled_start_at')
            ->first();

        $latestSummary = $patient->dentalRecords()
            ->whereNotNull('published_at')
            ->with('dentist:id,name')
            ->latest('published_at')
            ->first();

        $recentInvoices = $patient->invoices()
            ->withSum('payments', 'amount')
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        return view('patient.dashboard', [
            'patient' => $patient,
            'nextAppointment' => $nextAppointment,
            'pendingCount' => $appointments->where('status', 'pending')->count(),
            'outstandingBalance' => $patient->invoices()->withSum('payments', 'amount')->get()->sum->balance,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'recentAppointments' => $appointments->take(5),
            'latestSummary' => $latestSummary,
            'recentInvoices' => $recentInvoices,
        ]);
    }
}
