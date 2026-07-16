<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPatients = Patient::count();

        $todaysAppointments = Appointment::whereDate('appointment_date', today())
            ->with('patient')
            ->orderBy('appointment_time')
            ->get();

        $monthlyRevenue = Invoice::whereYear('invoice_date', now()->year)
            ->whereMonth('invoice_date', now()->month)
            ->sum('total');

        $revenueTrend = collect(range(5, 0))->map(function ($monthsAgo) {
            $month = now()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'value' => Invoice::whereYear('invoice_date', $month->year)
                    ->whereMonth('invoice_date', $month->month)
                    ->sum('total'),
            ];
        });

        $recentPatients = Patient::latest()->take(3)->get();

        return view('dashboard', [
            'totalPatients' => $totalPatients,
            'todaysAppointments' => $todaysAppointments,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueTrend' => $revenueTrend,
            'recentPatients' => $recentPatients,
        ]);
    }
}
