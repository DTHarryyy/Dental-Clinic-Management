<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PatientDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $patient = $request->user()->patient;
        $now = now();

        $nextAppointment = $patient->appointments()
            ->with(['dentist:id,name', 'serviceItems'])
            ->where('status', 'confirmed')
            ->where('scheduled_start_at', '>=', $now)
            ->orderBy('scheduled_start_at')
            ->first();

        $recentAppointments = $patient->appointments()
            ->with(['dentist:id,name', 'serviceItems'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $latestSummary = $patient->dentalRecords()
            ->whereNotNull('published_at')
            ->with('dentist:id,name')
            ->latest('published_at')
            ->first();

        $recentInvoices = $patient->invoices()
            ->withSum('verifiedPayments', 'amount')
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        return view('patient.dashboard', [
            'patient' => $patient,
            'nextAppointment' => $nextAppointment,
            'pendingCount' => $patient->appointments()->where('status', 'pending')->count(),
            'outstandingBalance' => $patient->invoices()->withSum('verifiedPayments', 'amount')->get(['id', 'total'])->sum->balance,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'recentAppointments' => $recentAppointments,
            'latestSummary' => $latestSummary,
            'recentInvoices' => $recentInvoices,
        ]);
    }
}
