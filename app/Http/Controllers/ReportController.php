<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now();
        $rangeKey = $from->format('Y-m-d').':'.$to->format('Y-m-d');

        $invoiceTotals = Invoice::whereBetween('invoice_date', [$from, $to])
            ->selectRaw('SUM(total) as revenue, COUNT(*) as invoice_count')
            ->first();

        $revenue = $invoiceTotals->revenue ?? 0;
        $appointmentsCount = Appointment::whereBetween('appointment_date', [$from, $to])->count();
        $newPatients = Patient::whereBetween('created_at', [$from, $to])->count();
        $avgBill = $revenue > 0 ? $revenue / max($invoiceTotals->invoice_count, 1) : 0;

        $revenueTrend = Cache::remember('dashboard:revenue-trend:'.now()->format('Y-m'), now()->addHour(), function () {
            return collect(range(5, 0))->map(function ($monthsAgo) {
                $month = now()->subMonths($monthsAgo);

                return [
                    'label' => $month->format('M'),
                    'value' => Invoice::whereYear('invoice_date', $month->year)->whereMonth('invoice_date', $month->month)->sum('total'),
                ];
            });
        });

        $serviceBreakdown = Cache::remember("reports:service-breakdown:{$rangeKey}", now()->addMinutes(30), function () use ($from, $to) {
            return DentalRecord::whereBetween('treatment_date', [$from, $to])
                ->selectRaw('procedure, count(*) as sessions, sum(treatment_fee) as revenue')
                ->groupBy('procedure')
                ->orderByDesc('sessions')
                ->get();
        });

        $totalSessions = max($serviceBreakdown->sum('sessions'), 1);

        $appointmentVolume = Cache::remember("reports:appointment-volume:{$rangeKey}", now()->addMinutes(30), function () use ($to) {
            return collect(range(3, 0))->map(function ($weeksAgo) use ($to) {
                $weekStart = $to->copy()->subWeeks($weeksAgo)->startOfWeek();
                $weekEnd = $weekStart->copy()->endOfWeek();

                return [
                    'label' => 'Week of '.$weekStart->format('M j'),
                    'value' => Appointment::whereBetween('appointment_date', [$weekStart, $weekEnd])->count(),
                ];
            });
        });

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'appointmentsCount' => $appointmentsCount,
            'newPatients' => $newPatients,
            'avgBill' => $avgBill,
            'revenueTrend' => $revenueTrend,
            'serviceBreakdown' => $serviceBreakdown,
            'totalSessions' => $totalSessions,
            'appointmentVolume' => $appointmentVolume,
        ]);
    }
}
