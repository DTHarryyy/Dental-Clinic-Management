<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\DentalRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\AnalyticsDateRange;
use App\Support\DomainCache;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAnalytics
{
    public function forUser(User $user, AnalyticsDateRange $range): array
    {
        $scope = $user->roleEnum() === Role::Dentist ? "dentist:{$user->id}" : $user->role;

        // This key is version-stamped by DomainCache and bumped after every commit that
        // touches appointments/records/invoices/payments/users (see AppServiceProvider),
        // so correctness comes from that bump, not this TTL — it only bounds how long a
        // truly stale entry could survive if a bump were ever missed. An hour is plenty.
        return Cache::remember(
            DomainCache::key('dashboard', "analytics:{$scope}:{$range->key()}"),
            3600,
            fn () => $this->buildAnalytics($user, $range)
        );
    }

    public function todaySchedule(User $user): Collection
    {
        $scope = $user->roleEnum() === Role::Dentist ? "dentist:{$user->id}" : $user->role;

        // Also version-stamped and write-bumped like forUser() above; the key already
        // rolls over at local midnight on its own, so this TTL just bounds staleness
        // between writes, same reasoning as forUser() but shorter since "today's
        // schedule" is the more time-sensitive of the two.
        return Cache::remember(
            DomainCache::key('dashboard', "schedule:{$scope}:".now(AnalyticsDateRange::TIMEZONE)->format('Y-m-d')),
            300,
            function () use ($user) {
                $today = CarbonImmutable::now(AnalyticsDateRange::TIMEZONE)->startOfDay();

                return Appointment::query()
                    ->with(['serviceItems' => fn ($query) => $query->when(
                        $user->roleEnum() === Role::Dentist,
                        fn ($query) => $query->select(['id', 'appointment_id', 'service_id', 'name_snapshot', 'duration_minutes_snapshot', 'display_order'])
                    )])
                    ->leftJoin('users as dentists', 'dentists.id', '=', 'appointments.dentist_id')
                    ->select([
                        'appointments.id', 'appointments.full_name', 'appointments.appointment_date',
                        'appointments.appointment_time', 'appointments.preferred_date',
                        'appointments.preferred_time_window', 'appointments.requested_start_at',
                        'appointments.scheduled_start_at', 'appointments.service',
                        'appointments.status', 'dentists.name as dentist_name',
                    ])
                    ->when($user->roleEnum() === Role::Dentist, fn (Builder $query) => $query->where('appointments.dentist_id', $user->id))
                    ->where(fn (Builder $query) => $this->whereEffectiveBetween($query, $today, $today->endOfDay(), 'appointments.'))
                    ->orderByRaw('CASE WHEN appointments.scheduled_start_at IS NULL AND appointments.requested_start_at IS NULL THEN 1 ELSE 0 END')
                    ->orderByRaw('COALESCE(appointments.scheduled_start_at, appointments.requested_start_at)')
                    ->limit(8)
                    ->get();
            }
        );
    }

    private function buildAnalytics(User $user, AnalyticsDateRange $range): array
    {
        $role = $user->roleEnum();
        $isDentist = $role === Role::Dentist;
        $dentistId = $isDentist ? $user->id : null;
        $appointments = $this->appointmentsForComparison($range, $dentistId);
        $currentAppointments = $appointments->filter(fn (Appointment $appointment) => $range->contains($this->effectiveAt($appointment)))->values();
        $previousAppointments = $appointments->filter(fn (Appointment $appointment) => $range->containsPrevious($this->effectiveAt($appointment)))->values();

        $currentCompletion = $this->completionRate($currentAppointments, $range->to);
        $previousCompletion = $this->completionRate($previousAppointments, $range->previousTo);
        $statusCounts = collect(['pending', 'confirmed', 'completed', 'cancelled'])
            ->mapWithKeys(fn (string $status) => [$status => $currentAppointments->where('status', $status)->count()]);

        $patientDates = collect();
        if (! $isDentist) {
            $patientDates = Patient::query()
                ->whereBetween('created_at', [$range->previousUtcFrom(), $range->utcTo()])
                ->pluck('created_at')
                ->map(fn ($date) => CarbonImmutable::parse($date)->setTimezone(AnalyticsDateRange::TIMEZONE));
        }
        $newPatients = $patientDates->filter(fn (CarbonImmutable $date) => $range->contains($date))->count();
        $previousPatients = $patientDates->filter(fn (CarbonImmutable $date) => $range->containsPrevious($date))->count();

        $payments = collect();
        $snapshot = null;
        if (! $isDentist) {
            $payments = Payment::query()
                ->verified()
                ->select(['amount', 'paid_at'])
                ->whereBetween('paid_at', [$range->previousUtcFrom(), $range->utcTo()])
                ->get();
            $snapshot = $this->financialSnapshot();
        }
        $currentPayments = $payments->filter(fn (Payment $payment) => $range->contains(CarbonImmutable::instance($payment->paid_at)->setTimezone(AnalyticsDateRange::TIMEZONE)));
        $previousPayments = $payments->filter(fn (Payment $payment) => $range->containsPrevious(CarbonImmutable::instance($payment->paid_at)->setTimezone(AnalyticsDateRange::TIMEZONE)));
        $revenue = (float) $currentPayments->sum('amount');
        $previousRevenue = (float) $previousPayments->sum('amount');

        $records = collect();
        if ($role !== Role::Receptionist) {
            $recordColumns = ['procedure', 'patient_id', 'treatment_date'];
            if (! $isDentist) {
                $recordColumns[] = 'treatment_fee';
            }
            $records = DentalRecord::query()
                ->select($recordColumns)
                ->whereDate('treatment_date', '>=', $range->previousFrom->toDateString())
                ->whereDate('treatment_date', '<=', $range->to->toDateString())
                ->when($dentistId, fn (Builder $query) => $query->where('dentist_id', $dentistId))
                ->get();
        }
        $currentRecords = $records->filter(fn (DentalRecord $record) => $this->dateBetween($record->treatment_date, $range->from, $range->to));
        $previousRecords = $records->filter(fn (DentalRecord $record) => $this->dateBetween($record->treatment_date, $range->previousFrom, $range->previousTo));

        $breakdown = $role === Role::Receptionist
            ? $this->bookedServiceBreakdown($currentAppointments)
            : $this->treatmentBreakdown($currentRecords, ! $isDentist);

        $kpis = match ($role) {
            Role::Dentist => [
                $this->kpi('Assigned appointments', $currentAppointments->count(), 'number', $previousAppointments->count(), 'fa-calendar-days', route('appointments.index')),
                $this->kpi('Treatment sessions', $currentRecords->count(), 'number', $previousRecords->count(), 'fa-tooth', route('records.index')),
                $this->kpi('Patients treated', $currentRecords->pluck('patient_id')->unique()->count(), 'number', $previousRecords->pluck('patient_id')->unique()->count(), 'fa-user-group', route('patients.index')),
                $this->kpi('Completion rate', $currentCompletion, 'percent', $previousCompletion, 'fa-circle-check', route('appointments.index')),
            ],
            Role::Receptionist => [
                $this->kpi('Appointments', $currentAppointments->count(), 'number', $previousAppointments->count(), 'fa-calendar-days', route('appointments.index')),
                $this->kpi('Collected payments', $revenue, 'currency', $previousRevenue, 'fa-money-bill-wave', route('billing.index')),
                $this->kpi('New patients', $newPatients, 'number', $previousPatients, 'fa-user-plus', route('patients.index')),
                $this->snapshotKpi('Pending confirmations', (int) $snapshot->pending_appointments, 'number', 'Needs confirmation', 'fa-clock', route('appointments.index', ['status' => 'Pending'])),
            ],
            default => [
                $this->kpi('Collected revenue', $revenue, 'currency', $previousRevenue, 'fa-money-bill-wave', route('billing.index')),
                $this->kpi('Appointments', $currentAppointments->count(), 'number', $previousAppointments->count(), 'fa-calendar-days', route('appointments.index')),
                $this->kpi('New patients', $newPatients, 'number', $previousPatients, 'fa-user-plus', route('patients.index')),
                $this->kpi('Completion rate', $currentCompletion, 'percent', $previousCompletion, 'fa-circle-check', route('appointments.index')),
            ],
        };

        $attention = $isDentist
            ? [
                $this->attention('Pending appointments', $currentAppointments->where('status', 'pending')->count(), 'Within selected period', 'amber', 'fa-clock', route('appointments.index', ['status' => 'Pending'])),
                $this->attention('Cancelled appointments', $currentAppointments->where('status', 'cancelled')->count(), 'Within selected period', 'red', 'fa-calendar-xmark', route('appointments.index', ['status' => 'Cancelled'])),
            ]
            : [
                $this->attention('Outstanding balance', $this->currency((float) $snapshot->outstanding_balance), $snapshot->outstanding_count.' open invoice(s) · As of today', 'amber', 'fa-file-invoice-dollar', route('billing.index')),
                $this->attention('Overdue balance', $this->currency((float) $snapshot->overdue_balance), $snapshot->overdue_count.' overdue invoice(s) · As of today', 'red', 'fa-triangle-exclamation', route('billing.index', ['status' => 'Unpaid'])),
                $this->attention('Pending requests', (int) $snapshot->pending_appointments, 'Awaiting confirmation', 'blue', 'fa-clock', route('appointments.index', ['status' => 'Pending'])),
                $this->attention('Unassigned appointments', (int) $snapshot->unassigned_appointments, 'Upcoming and without a dentist', 'violet', 'fa-user-clock', route('appointments.index')),
            ];

        $primarySeries = $isDentist
            ? $this->series($currentAppointments, $range, fn (Appointment $appointment) => $this->effectiveAt($appointment), fn () => 1)
            : $this->series($currentPayments, $range, fn (Payment $payment) => CarbonImmutable::instance($payment->paid_at)->setTimezone(AnalyticsDateRange::TIMEZONE), fn (Payment $payment) => (float) $payment->amount);

        return [
            'range' => [
                'period' => $range->period,
                'from' => $range->from->toDateString(),
                'to' => $range->to->toDateString(),
                'label' => $range->label(),
            ],
            'kpis' => $kpis,
            'attentionTitle' => $isDentist ? 'Workload signals' : 'Needs attention',
            'attention' => $attention,
            'charts' => [
                'primary' => [
                    'type' => 'line',
                    'title' => $isDentist ? 'Appointment volume' : 'Collections trend',
                    'subtitle' => $range->label(),
                    'labels' => $primarySeries['labels'],
                    'datasets' => [[
                        'label' => $isDentist ? 'Appointments' : 'Collected payments',
                        'data' => $primarySeries['values'],
                        'format' => $isDentist ? 'number' : 'currency',
                    ]],
                    'summary' => $isDentist
                        ? $currentAppointments->count().' assigned appointment(s) in the selected period.'
                        : $this->currency($revenue).' collected in the selected period.',
                ],
                'status' => [
                    'type' => 'doughnut',
                    'title' => 'Appointment status',
                    'subtitle' => $range->label(),
                    'labels' => ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                    'datasets' => [['label' => 'Appointments', 'data' => $statusCounts->values()->all(), 'format' => 'number']],
                    'summary' => $statusCounts->map(fn ($count, $status) => ucfirst($status).": {$count}")->join(', ').'.',
                ],
                'breakdown' => [
                    'type' => 'bar',
                    'title' => $role === Role::Receptionist ? 'Booked service demand' : 'Treatment mix',
                    'subtitle' => $range->label(),
                    'labels' => $breakdown->pluck('label')->all(),
                    'datasets' => [['label' => 'Sessions', 'data' => $breakdown->pluck('sessions')->all(), 'format' => 'number']],
                    'summary' => $breakdown->isEmpty()
                        ? 'No service activity in the selected period.'
                        : $breakdown->map(fn ($item) => $item['label'].': '.$item['sessions'].' session(s)')->join(', ').'.',
                    'rows' => $breakdown->all(),
                ],
            ],
        ];
    }

    private function appointmentsForComparison(AnalyticsDateRange $range, ?int $dentistId): Collection
    {
        return Appointment::query()
            ->select(['id', 'patient_id', 'dentist_id', 'status', 'scheduled_start_at', 'requested_start_at', 'preferred_date', 'appointment_date'])
            ->when($dentistId, fn (Builder $query) => $query->where('dentist_id', $dentistId))
            ->where(fn (Builder $query) => $this->whereEffectiveBetween($query, $range->previousFrom, $range->to))
            ->get();
    }

    private function whereEffectiveBetween(Builder $query, CarbonImmutable $from, CarbonImmutable $to, string $prefix = ''): void
    {
        $query
            ->where(function (Builder $branch) use ($from, $to, $prefix) {
                $branch->whereNotNull($prefix.'scheduled_start_at')->whereBetween($prefix.'scheduled_start_at', [$from->setTimezone('UTC'), $to->setTimezone('UTC')]);
            })
            ->orWhere(function (Builder $branch) use ($from, $to, $prefix) {
                $branch->whereNull($prefix.'scheduled_start_at')->whereNotNull($prefix.'requested_start_at')->whereBetween($prefix.'requested_start_at', [$from->setTimezone('UTC'), $to->setTimezone('UTC')]);
            })
            ->orWhere(function (Builder $branch) use ($from, $to, $prefix) {
                $branch->whereNull($prefix.'scheduled_start_at')->whereNull($prefix.'requested_start_at')->whereNotNull($prefix.'preferred_date')
                    ->whereDate($prefix.'preferred_date', '>=', $from->toDateString())->whereDate($prefix.'preferred_date', '<=', $to->toDateString());
            })
            ->orWhere(function (Builder $branch) use ($from, $to, $prefix) {
                $branch->whereNull($prefix.'scheduled_start_at')->whereNull($prefix.'requested_start_at')->whereNull($prefix.'preferred_date')
                    ->whereDate($prefix.'appointment_date', '>=', $from->toDateString())->whereDate($prefix.'appointment_date', '<=', $to->toDateString());
            });
    }

    private function effectiveAt(Appointment $appointment): CarbonImmutable
    {
        if ($appointment->scheduled_start_at) {
            return CarbonImmutable::instance($appointment->scheduled_start_at)->setTimezone(AnalyticsDateRange::TIMEZONE);
        }
        if ($appointment->requested_start_at) {
            return CarbonImmutable::instance($appointment->requested_start_at)->setTimezone(AnalyticsDateRange::TIMEZONE);
        }

        return $this->localDate($appointment->preferred_date ?? $appointment->appointment_date);
    }

    private function localDate($date): CarbonImmutable
    {
        return CarbonImmutable::parse($date->format('Y-m-d'), AnalyticsDateRange::TIMEZONE)->startOfDay();
    }

    private function dateBetween($date, CarbonImmutable $from, CarbonImmutable $to): bool
    {
        $value = $date->format('Y-m-d');

        return $value >= $from->toDateString() && $value <= $to->toDateString();
    }

    private function completionRate(Collection $appointments, CarbonImmutable $periodEnd): float
    {
        $cutoff = min(CarbonImmutable::now(AnalyticsDateRange::TIMEZONE)->getTimestamp(), $periodEnd->getTimestamp());
        $elapsed = $appointments->filter(fn (Appointment $appointment) => $this->effectiveAt($appointment)->getTimestamp() <= $cutoff);

        return $elapsed->isEmpty() ? 0 : round($elapsed->where('status', 'completed')->count() / $elapsed->count() * 100, 1);
    }

    private function financialSnapshot(): object
    {
        $today = CarbonImmutable::now(AnalyticsDateRange::TIMEZONE)->startOfDay();
        $nowUtc = CarbonImmutable::now('UTC');

        return DB::selectOne(
            "SELECT
                COALESCE(SUM(CASE WHEN invoices.total - COALESCE(payment_totals.paid, 0) > 0 THEN invoices.total - COALESCE(payment_totals.paid, 0) ELSE 0 END), 0) AS outstanding_balance,
                COUNT(CASE WHEN invoices.total - COALESCE(payment_totals.paid, 0) > 0 THEN 1 END) AS outstanding_count,
                COALESCE(SUM(CASE WHEN invoices.due_date < ? AND invoices.total - COALESCE(payment_totals.paid, 0) > 0 THEN invoices.total - COALESCE(payment_totals.paid, 0) ELSE 0 END), 0) AS overdue_balance,
                COUNT(CASE WHEN invoices.due_date < ? AND invoices.total - COALESCE(payment_totals.paid, 0) > 0 THEN 1 END) AS overdue_count,
                (SELECT COUNT(*) FROM appointments WHERE status = 'pending') AS pending_appointments,
                (SELECT COUNT(*) FROM appointments WHERE dentist_id IS NULL AND status IN ('pending', 'confirmed') AND (
                    (scheduled_start_at IS NOT NULL AND scheduled_start_at >= ?) OR
                    (scheduled_start_at IS NULL AND requested_start_at IS NOT NULL AND requested_start_at >= ?) OR
                    (scheduled_start_at IS NULL AND requested_start_at IS NULL AND COALESCE(preferred_date, appointment_date) >= ?)
                )) AS unassigned_appointments
            FROM invoices
            LEFT JOIN (SELECT invoice_id, SUM(amount) AS paid FROM payments WHERE status = 'verified' GROUP BY invoice_id) payment_totals ON payment_totals.invoice_id = invoices.id",
            [$today->toDateString(), $today->toDateString(), $nowUtc, $nowUtc, $today->toDateString()]
        );
    }

    private function treatmentBreakdown(Collection $records, bool $includeFees): Collection
    {
        return $this->collapseBreakdown(
            $records->groupBy('procedure')->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'sessions' => $items->count(),
                'fees' => $includeFees ? (float) $items->sum('treatment_fee') : null,
                'fees_display' => $includeFees ? $this->currency((float) $items->sum('treatment_fee')).' recorded fees' : null,
            ])->sortByDesc('sessions')->values()
        );
    }

    private function bookedServiceBreakdown(Collection $appointments): Collection
    {
        if ($appointments->isEmpty()) {
            return collect();
        }

        $items = AppointmentService::query()
            ->whereIn('appointment_id', $appointments->pluck('id'))
            ->selectRaw('name_snapshot, COUNT(*) as sessions')
            ->groupBy('name_snapshot')
            ->orderByDesc('sessions')
            ->get()
            ->map(fn (AppointmentService $item) => [
                'label' => $item->name_snapshot,
                'sessions' => (int) $item->sessions,
                'fees' => null,
                'fees_display' => null,
            ]);

        return $this->collapseBreakdown($items);
    }

    private function collapseBreakdown(Collection $items): Collection
    {
        if ($items->count() <= 6) {
            return $items->values();
        }

        $visible = $items->take(5)->values();
        $other = $items->skip(5);
        $visible->push([
            'label' => 'Other',
            'sessions' => (int) $other->sum('sessions'),
            'fees' => $other->contains(fn ($item) => $item['fees'] !== null) ? (float) $other->sum('fees') : null,
            'fees_display' => $other->contains(fn ($item) => $item['fees'] !== null) ? $this->currency((float) $other->sum('fees')).' recorded fees' : null,
        ]);

        return $visible;
    }

    private function series(Collection $items, AnalyticsDateRange $range, callable $date, callable $value): array
    {
        $buckets = collect();
        if ($range->days() <= 31) {
            for ($cursor = $range->from->startOfDay(); $cursor->lte($range->to); $cursor = $cursor->addDay()) {
                $buckets->push(['from' => $cursor, 'to' => $cursor->endOfDay(), 'label' => $cursor->format('M j')]);
            }
        } elseif ($range->days() <= 120) {
            for ($cursor = $range->from->startOfDay(); $cursor->lte($range->to); $cursor = $cursor->addDays(7)) {
                $end = $cursor->addDays(6)->endOfDay()->min($range->to);
                $buckets->push(['from' => $cursor, 'to' => $end, 'label' => $cursor->format('M j').'–'.$end->format('M j')]);
            }
        } else {
            for ($cursor = $range->from->startOfMonth(); $cursor->lte($range->to); $cursor = $cursor->addMonth()) {
                $buckets->push([
                    'from' => $cursor->max($range->from),
                    'to' => $cursor->endOfMonth()->min($range->to),
                    'label' => $cursor->format('M Y'),
                ]);
            }
        }

        return [
            'labels' => $buckets->pluck('label')->all(),
            'values' => $buckets->map(fn (array $bucket) => round((float) $items
                ->filter(fn ($item) => $date($item)->betweenIncluded($bucket['from'], $bucket['to']))
                ->sum(fn ($item) => $value($item)), 2))->all(),
        ];
    }

    private function kpi(string $label, float|int $value, string $format, float|int $previous, string $icon, string $url): array
    {
        return [
            'label' => $label,
            'value' => $this->format($value, $format),
            'comparison' => $this->comparison($value, $previous, $format === 'percent'),
            'icon' => $icon,
            'url' => $url,
        ];
    }

    private function snapshotKpi(string $label, float|int $value, string $format, string $subtitle, string $icon, string $url): array
    {
        return ['label' => $label, 'value' => $this->format($value, $format), 'comparison' => ['text' => $subtitle, 'direction' => 'flat'], 'icon' => $icon, 'url' => $url];
    }

    private function comparison(float|int $current, float|int $previous, bool $points): array
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0
                ? ['text' => 'No change vs previous period', 'direction' => 'flat']
                : ['text' => 'New vs previous period', 'direction' => 'up'];
        }

        $change = $points ? $current - $previous : (($current - $previous) / abs($previous)) * 100;
        $direction = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat');
        $suffix = $points ? ' points' : '%';

        return ['text' => number_format(abs($change), 1).$suffix.' vs previous period', 'direction' => $direction];
    }

    private function attention(string $label, string|int $value, string $meta, string $tone, string $icon, string $url): array
    {
        return compact('label', 'value', 'meta', 'tone', 'icon', 'url');
    }

    private function format(float|int $value, string $format): string
    {
        return match ($format) {
            'currency' => $this->currency((float) $value),
            'percent' => number_format((float) $value, 1).'%',
            default => number_format((float) $value),
        };
    }

    private function currency(float $value): string
    {
        return '₱'.number_format($value, 2);
    }
}
