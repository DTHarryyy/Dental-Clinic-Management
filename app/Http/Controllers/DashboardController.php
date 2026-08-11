<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Support\DomainCache;
use App\Support\FinancialTrends;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $summary = Cache::remember(DomainCache::key('dashboard', 'summary'), 30, fn () => [
            'totalPatients' => Patient::count(),
            'monthlyRevenue' => (float) Payment::whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'revenueTrend' => FinancialTrends::sixMonthRevenue(),
            'recentPatients' => Patient::select(['id', 'first_name', 'last_name', 'created_at'])->latest()->take(3)->get(),
        ]);

        $todaysAppointments = Appointment::with('serviceItems')->where(function ($query) {
                $query->whereDate('scheduled_start_at', today())->orWhereDate('preferred_date', today())->orWhereDate('appointment_date', today());
            })
            ->select(['id', 'full_name', 'appointment_date', 'appointment_time', 'preferred_date', 'preferred_time_window', 'scheduled_start_at', 'service', 'status'])
            ->orderBy('scheduled_start_at')
            ->get();

        return view('dashboard', [
            ...$summary,
            'todaysAppointments' => $todaysAppointments,
        ]);
    }
}
