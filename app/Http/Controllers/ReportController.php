<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Support\DomainCache;
use App\Support\FinancialTrends;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now();
        $rangeKey = $from->format('Y-m-d').':'.$to->format('Y-m-d');

        $metrics = Cache::remember(DomainCache::key('reports', "metrics:{$rangeKey}"), 60, fn () => DB::selectOne(
            'SELECT
                (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE paid_at BETWEEN ? AND ?) AS revenue,
                (SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ?) AS appointments_count,
                (SELECT COUNT(*) FROM patients WHERE created_at BETWEEN ? AND ?) AS new_patients,
                (SELECT COALESCE(AVG(total), 0) FROM invoices WHERE invoice_date BETWEEN ? AND ?) AS avg_bill',
            [
                $from->copy()->startOfDay(), $to->copy()->endOfDay(),
                $from->toDateString(), $to->toDateString(),
                $from->copy()->startOfDay(), $to->copy()->endOfDay(),
                $from->toDateString(), $to->toDateString(),
            ]
        ));

        $revenueTrend = Cache::remember(DomainCache::key('reports', 'six-month-revenue'), 60, fn () => FinancialTrends::sixMonthRevenue());

        $serviceBreakdown = Cache::remember(DomainCache::key('reports', "service-breakdown:{$rangeKey}"), now()->addMinutes(30), function () use ($from, $to) {
            return DentalRecord::whereBetween('treatment_date', [$from, $to])
                ->selectRaw('procedure, count(*) as sessions, sum(treatment_fee) as revenue')
                ->groupBy('procedure')
                ->orderByDesc('sessions')
                ->get();
        });

        $totalSessions = max($serviceBreakdown->sum('sessions'), 1);

        $appointmentVolume = Cache::remember(DomainCache::key('reports', "appointment-volume:{$rangeKey}"), now()->addMinutes(30), function () use ($to) {
            $firstWeek = $to->copy()->subWeeks(3)->startOfWeek();
            $appointments = Appointment::query()
                ->select('appointment_date')
                ->whereBetween('appointment_date', [$firstWeek, $to->copy()->endOfWeek()])
                ->get();

            return collect(range(3, 0))->map(function ($weeksAgo) use ($to, $appointments) {
                $weekStart = $to->copy()->subWeeks($weeksAgo)->startOfWeek();
                $weekEnd = $weekStart->copy()->endOfWeek();

                return [
                    'label' => 'Week of '.$weekStart->format('M j'),
                    'value' => $appointments->filter(fn (Appointment $appointment) => $appointment->appointment_date->betweenIncluded($weekStart, $weekEnd))->count(),
                ];
            });
        });

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'revenue' => (float) $metrics->revenue,
            'appointmentsCount' => (int) $metrics->appointments_count,
            'newPatients' => (int) $metrics->new_patients,
            'avgBill' => (float) $metrics->avg_bill,
            'revenueTrend' => $revenueTrend,
            'serviceBreakdown' => $serviceBreakdown,
            'totalSessions' => $totalSessions,
            'appointmentVolume' => $appointmentVolume,
        ]);
    }
}
