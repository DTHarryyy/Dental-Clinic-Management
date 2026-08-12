<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\DomainCache;
use App\Support\ReportDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ClinicReportService
{
    public const CSV_DATASETS = ['payments', 'invoices', 'appointments', 'patients', 'services', 'dentists'];

    public function report(ReportDateRange $range, string $tab = 'overview', bool $full = false): array
    {
        $section = $tab === 'patients-services' ? 'patientsServices' : $tab;

        return Cache::remember(
            DomainCache::key('reports', 'presentation-v2:'.($full ? 'full' : 'screen:'.$tab).':'.$range->key()),
            300,
            fn () => $this->build($range, $full ? null : collect(['overview', $section])->unique()->all()),
        );
    }

    public function csvRows(string $dataset, ReportDateRange $range): array
    {
        abort_unless(in_array($dataset, self::CSV_DATASETS, true), 404);

        return Cache::remember(
            DomainCache::key('reports', "csv:{$dataset}:{$range->key()}"),
            300,
            fn () => $this->buildCsvRows($dataset, $range),
        );
    }

    private function buildCsvRows(string $dataset, ReportDateRange $range): array
    {
        $tab = match ($dataset) {
            'payments', 'invoices' => 'financial',
            'appointments' => 'appointments',
            'patients', 'services' => 'patients-services',
            'dentists' => 'dentists',
        };
        $report = $this->report($range, $tab);

        return match ($dataset) {
            'payments' => [
                ['Payment date', 'Invoice', 'Patient', 'Method', 'Reference', 'Amount', 'Receiver'],
                collect($report['financial']['payments'])->map(fn ($row) => [
                    $row['date'], $row['invoice'], $row['patient'], $row['method'], $row['reference'], $row['amount'], $row['receiver'],
                ]),
            ],
            'invoices' => [
                ['Invoice', 'Invoice date', 'Due date', 'Patient', 'Subtotal', 'Discount', 'Invoiced total', 'Paid amount', 'Balance', 'Status', 'Aging bucket'],
                collect($report['financial']['invoices'])->map(fn ($row) => [
                    $row['invoice'], $row['invoice_date'], $row['due_date'], $row['patient'], $row['subtotal'], $row['discount'], $row['total'], $row['paid'], $row['balance'], $row['status'], $row['aging'],
                ]),
            ],
            'appointments' => [
                ['Effective schedule', 'Patient', 'Dentist', 'Services', 'Duration (minutes)', 'Status'],
                collect($report['appointments']['rows'])->map(fn ($row) => [
                    $row['schedule'], $row['patient'], $row['dentist'], $row['services'], $row['duration'], $row['status'],
                ]),
            ],
            'patients' => [
                ['Patient ID', 'Patient name', 'Registration date', 'Status', 'Appointment count', 'Treatment count', 'Recorded treatment fees'],
                collect($report['patientsServices']['patients'])->map(fn ($row) => [
                    $row['identifier'], $row['name'], $row['registered'], $row['status'], $row['appointments'], $row['treatments'], $row['fees'],
                ]),
            ],
            'services' => [
                ['Service', 'Booked appointments', 'Completed sessions', 'Share of sessions', 'Recorded treatment fees', 'Average fee'],
                collect($report['patientsServices']['services'])->map(fn ($row) => [
                    $row['service'], $row['booked'], $row['sessions'], $row['share'], $row['fees'], $row['average_fee'],
                ]),
            ],
            'dentists' => [
                ['Dentist', 'Account status', 'Assigned appointments', 'Completed appointments', 'Cancelled appointments', 'Completion rate', 'Treatment sessions', 'Distinct patients', 'Recorded treatment fees', 'Average session fee'],
                collect($report['dentists']['rows'])->map(fn ($row) => [
                    $row['dentist'], $row['status'], $row['assigned'], $row['completed'], $row['cancelled'], $row['completion_rate'], $row['sessions'], $row['patients'], $row['fees'], $row['average_fee'],
                ]),
            ],
        };
    }

    private function build(ReportDateRange $range, ?array $only = null): array
    {
        $appointments = $this->appointmentsFor($range);
        $currentAppointments = $appointments->filter(fn ($item) => $range->contains($item['at']))->values();
        $previousAppointments = $appointments->filter(fn ($item) => $range->containsPrevious($item['at']))->values();

        $records = DentalRecord::query()
            ->leftJoin('patients', 'patients.id', '=', 'dental_records.patient_id')
            ->leftJoin('users as dentists', 'dentists.id', '=', 'dental_records.dentist_id')
            ->whereDate('treatment_date', '>=', $range->previousFrom->toDateString())
            ->whereDate('treatment_date', '<=', $range->to->toDateString())
            ->get([
                'dental_records.id', 'dental_records.patient_id', 'dental_records.dentist_id', 'treatment_date',
                'procedure', 'treatment_fee', 'patients.first_name', 'patients.last_name', 'dentists.name as dentist_name',
            ])->map(function ($record) {
                $record->at = CarbonImmutable::parse($record->treatment_date->format('Y-m-d'), ReportDateRange::TIMEZONE);
                $record->patient_name = trim($record->first_name.' '.$record->last_name) ?: 'Unknown patient';

                return $record;
            });
        $currentRecords = $records->filter(fn ($record) => $range->contains($record->at))->values();
        $previousRecords = $records->filter(fn ($record) => $range->containsPrevious($record->at))->values();

        $payments = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->leftJoin('patients', 'patients.id', '=', 'invoices.patient_id')
            ->leftJoin('users as receivers', 'receivers.id', '=', 'payments.received_by')
            ->whereBetween('payments.paid_at', [$range->previousUtcFrom(), $range->utcTo()])
            ->get([
                'payments.id', 'payments.invoice_id', 'payments.amount', 'payments.method', 'payments.reference',
                'payments.paid_at', 'patients.first_name', 'patients.last_name', 'receivers.name as receiver_name',
            ]);
        $currentPayments = $payments->filter(fn ($payment) => $range->contains($this->localTimestamp($payment->paid_at)))->values();
        $previousPayments = $payments->filter(fn ($payment) => $range->containsPrevious($this->localTimestamp($payment->paid_at)))->values();

        $invoices = Invoice::query()
            ->leftJoin('patients', 'patients.id', '=', 'invoices.patient_id')
            ->select([
                'invoices.id', 'invoices.invoice_date', 'invoices.due_date', 'invoices.subtotal', 'invoices.discount',
                'invoices.total', 'patients.first_name', 'patients.last_name',
            ])
            ->selectSub(Payment::query()->selectRaw('COALESCE(SUM(amount), 0)')->whereColumn('invoice_id', 'invoices.id')->where('paid_at', '<=', $range->utcTo()), 'paid_to')
            ->selectSub(Payment::query()->selectRaw('COALESCE(SUM(amount), 0)')->whereColumn('invoice_id', 'invoices.id')->where('paid_at', '<=', $range->previousUtcTo()), 'paid_previous')
            ->whereDate('invoice_date', '<=', $range->to->toDateString())
            ->get();

        $activePatientIds = $currentAppointments->pluck('patient_id')->merge($currentRecords->pluck('patient_id'))->filter()->unique();
        $comparisonActivePatientIds = $activePatientIds
            ->merge($previousAppointments->pluck('patient_id'))
            ->merge($previousRecords->pluck('patient_id'))->filter()->unique();
        $priorAppointments = $comparisonActivePatientIds->isEmpty() ? collect() : Appointment::query()
            ->whereIn('patient_id', $comparisonActivePatientIds)
            ->where(fn (Builder $query) => $this->whereEffectiveBefore($query, $range->from))
            ->get(['patient_id', 'scheduled_start_at', 'requested_start_at', 'preferred_date', 'appointment_date']);
        $priorPatientIds = $priorAppointments->pluck('patient_id')->unique();
        $previousPriorPatientIds = $priorAppointments
            ->filter(fn (Appointment $appointment) => $this->effectiveAt($appointment)->lt($range->previousFrom))
            ->pluck('patient_id')->unique();
        $patients = Patient::query()
            ->where(function (Builder $query) use ($range, $activePatientIds) {
                $query->whereBetween('created_at', [$range->previousUtcFrom(), $range->utcTo()]);
                if ($activePatientIds->isNotEmpty()) {
                    $query->orWhereIn('id', $activePatientIds);
                }
            })
            ->get(['id', 'first_name', 'last_name', 'dob', 'gender', 'status', 'created_at']);

        $dentistIds = $currentAppointments->pluck('dentist_id')->merge($currentRecords->pluck('dentist_id'))->filter()->unique();
        $dentists = User::query()->where('role', 'dentist')->where(function (Builder $query) use ($dentistIds) {
            $query->where('status', 'active');
            if ($dentistIds->isNotEmpty()) {
                $query->orWhereIn('id', $dentistIds);
            }
        })->get(['id', 'name', 'status']);

        $currentInvoices = $invoices->filter(fn ($invoice) => $this->dateIn($invoice->invoice_date, $range->from, $range->to))->values();
        $previousInvoices = $invoices->filter(fn ($invoice) => $this->dateIn($invoice->invoice_date, $range->previousFrom, $range->previousTo))->values();
        $currentPatients = $patients->filter(fn ($patient) => $range->contains($this->localTimestamp($patient->created_at)))->values();
        $previousPatients = $patients->filter(fn ($patient) => $range->containsPrevious($this->localTimestamp($patient->created_at)))->values();

        $report = ['range' => $this->rangeMeta($range)];
        if ($only === null || in_array('overview', $only, true)) {
            $report['overview'] = $this->overview($range, $currentAppointments, $previousAppointments, $currentRecords, $previousRecords, $currentPayments, $previousPayments, $currentInvoices, $previousInvoices, $invoices, $currentPatients, $previousPatients, $dentists);
        }
        if ($only === null || in_array('financial', $only, true)) {
            $report['financial'] = $this->financial($range, $currentPayments, $previousPayments, $currentInvoices, $previousInvoices, $invoices);
        }
        if ($only === null || in_array('appointments', $only, true)) {
            $report['appointments'] = $this->appointmentReport($range, $currentAppointments, $previousAppointments, $dentists);
        }
        if ($only === null || in_array('patientsServices', $only, true)) {
            $report['patientsServices'] = $this->patientsServices($range, $currentAppointments, $previousAppointments, $currentRecords, $previousRecords, $patients, $currentPatients, $previousPatients, $priorPatientIds, $previousPriorPatientIds);
        }
        if ($only === null || in_array('dentists', $only, true)) {
            $report['dentists'] = $this->dentistReport($range, $currentAppointments, $currentRecords, $dentists);
        }

        return $report;
    }

    private function overview(ReportDateRange $range, Collection $appointments, Collection $previousAppointments, Collection $records, Collection $previousRecords, Collection $payments, Collection $previousPayments, Collection $invoices, Collection $previousInvoices, Collection $allInvoices, Collection $patients, Collection $previousPatients, Collection $dentists): array
    {
        $completion = $this->appointmentRate($appointments, $range->to, 'completed');
        $previousCompletion = $this->appointmentRate($previousAppointments, $range->previousTo, 'completed');
        $balances = $this->balances($allInvoices, $range->to, 'paid_to');
        $topTreatment = $records->groupBy('procedure')->map->count()->sortDesc()->keys()->first();
        $topDentist = $records->groupBy('dentist_id')->map->count()->sortDesc()->keys()->first();
        $topDentistName = $dentists->firstWhere('id', $topDentist)?->name;
        $status = $this->counts($appointments, 'status', ['pending', 'confirmed', 'completed', 'cancelled']);
        $treatment = $this->counts($records, 'procedure');
        $collectedAgainstInvoices = $invoices->sum(fn ($invoice) => min((float) $invoice->paid_to, (float) $invoice->total));

        return [
            'kpis' => [
                $this->kpi('Collected payments', $payments->sum('amount'), 'currency', $previousPayments->sum('amount')),
                $this->kpi('Invoiced total', $invoices->sum('total'), 'currency', $previousInvoices->sum('total')),
                $this->snapshotKpi('Outstanding balance', $balances['outstanding'], 'currency', 'As of period end'),
                $this->kpi('Appointments', $appointments->count(), 'number', $previousAppointments->count()),
                $this->kpi('New patients', $patients->count(), 'number', $previousPatients->count()),
                $this->kpi('Completion rate', $completion, 'percent', $previousCompletion, true),
            ],
            'charts' => [
                'overview-financial' => $this->dualTrendChart('Collections versus invoices', $range, [
                    ['label' => 'Collected payments', 'items' => $payments, 'date' => fn ($p) => $this->localTimestamp($p->paid_at), 'value' => fn ($p) => (float) $p->amount, 'format' => 'currency'],
                    ['label' => 'Invoiced total', 'items' => $invoices, 'date' => fn ($i) => $this->localDate($i->invoice_date), 'value' => fn ($i) => (float) $i->total, 'format' => 'currency'],
                ], ['layout' => 'wide', 'variant' => 'trend']),
                'overview-status' => $this->distributionChart('Appointment status', $status, 'Appointments', 'doughnut', 'number', ['layout' => 'compact', 'variant' => 'distribution', 'compact' => true]),
                'overview-patients' => $this->trendChart('Patient growth', $range, $patients, fn ($p) => $this->localTimestamp($p->created_at), fn () => 1, 'New patients', 'number', ['layout' => 'half', 'variant' => 'trend']),
                'overview-treatment' => $this->distributionChart('Treatment mix', $treatment, 'Sessions', 'doughnut', 'number', ['layout' => 'half', 'variant' => 'distribution']),
            ],
            'insights' => [
                $this->insight('Collection rate', $this->percent($invoices->sum('total') > 0 ? $collectedAgainstInvoices / $invoices->sum('total') * 100 : 0), 'Paid against invoices issued in this period', 'fa-chart-line'),
                $this->insight('Cancellation rate', $this->percent($this->appointmentRate($appointments, $range->to, 'cancelled')), 'Elapsed appointments only', 'fa-calendar-xmark'),
                $this->insight('Leading treatment', $topTreatment ?: 'No treatment data', 'Most completed sessions', 'fa-tooth'),
                $this->insight('Top dentist by sessions', $topDentistName ?: 'No dentist activity', 'Completed treatment records', 'fa-user-doctor'),
                $this->insight('Overdue balance', $this->money($balances['overdue']), 'Outstanding as of period end', 'fa-triangle-exclamation'),
            ],
        ];
    }

    private function financial(ReportDateRange $range, Collection $payments, Collection $previousPayments, Collection $invoices, Collection $previousInvoices, Collection $allInvoices): array
    {
        $balances = $this->balances($allInvoices, $range->to, 'paid_to');
        $previousBalances = $this->balances($allInvoices, $range->previousTo, 'paid_previous');
        $invoiceTotal = (float) $invoices->sum('total');
        $previousInvoiceTotal = (float) $previousInvoices->sum('total');
        $collectedAgainstInvoices = $invoices->sum(fn ($invoice) => min((float) $invoice->paid_to, (float) $invoice->total));
        $previousCollectedAgainstInvoices = $previousInvoices->sum(fn ($invoice) => min((float) $invoice->paid_previous, (float) $invoice->total));
        $collectionRate = $invoiceTotal > 0 ? $collectedAgainstInvoices / $invoiceTotal * 100 : 0;
        $previousCollectionRate = $previousInvoiceTotal > 0 ? $previousCollectedAgainstInvoices / $previousInvoiceTotal * 100 : 0;
        $invoiceRows = $allInvoices->map(fn ($invoice) => $this->invoiceRow($invoice, $range))->values();
        $selectedInvoiceRows = $invoices->map(fn ($invoice) => $this->invoiceRow($invoice, $range))->values();
        $aging = collect(['Not due', '1–30 days', '31–60 days', '61–90 days', '90+ days'])->map(function ($bucket) use ($invoiceRows) {
            $rows = $invoiceRows->where('aging', $bucket);

            return ['bucket' => $bucket, 'invoices' => $rows->count(), 'balance' => (float) $rows->sum('balance')];
        })->all();
        $paymentMethods = $this->counts($payments, 'method', [], 'Unspecified');
        $leadingMethod = $paymentMethods->sortDesc()->keys()->first();
        $largestBalance = (float) $invoiceRows->max('balance');

        return [
            'kpis' => [
                $this->kpi('Subtotal', $invoices->sum('subtotal'), 'currency', $previousInvoices->sum('subtotal')),
                $this->kpi('Discounts', $invoices->sum('discount'), 'currency', $previousInvoices->sum('discount')),
                $this->kpi('Invoiced total', $invoiceTotal, 'currency', $previousInvoiceTotal),
                $this->kpi('Collected payments', $payments->sum('amount'), 'currency', $previousPayments->sum('amount')),
                $this->snapshotKpi('Outstanding balance', $balances['outstanding'], 'currency', 'As of period end'),
                $this->snapshotKpi('Overdue balance', $balances['overdue'], 'currency', 'As of period end'),
                $this->kpi('Average invoice', $invoices->count() ? $invoiceTotal / $invoices->count() : 0, 'currency', $previousInvoices->count() ? $previousInvoiceTotal / $previousInvoices->count() : 0),
                $this->kpi('Collection rate', $collectionRate, 'percent', $previousCollectionRate, true),
            ],
            'charts' => [
                'financial-trend' => $this->dualTrendChart('Invoices and collections over time', $range, [
                    ['label' => 'Invoiced total', 'items' => $invoices, 'date' => fn ($i) => $this->localDate($i->invoice_date), 'value' => fn ($i) => (float) $i->total, 'format' => 'currency'],
                    ['label' => 'Collected payments', 'items' => $payments, 'date' => fn ($p) => $this->localTimestamp($p->paid_at), 'value' => fn ($p) => (float) $p->amount, 'format' => 'currency'],
                ], ['layout' => 'wide', 'variant' => 'trend']),
                'financial-methods' => $this->distributionChart('Payment methods', $paymentMethods, 'Payments', 'doughnut', 'number', ['layout' => 'compact', 'variant' => 'distribution', 'compact' => true]),
                'financial-status' => $this->distributionChart('Invoice status as of period end', $this->counts($selectedInvoiceRows, 'status'), 'Invoices', 'doughnut', 'number', ['layout' => 'compact', 'variant' => 'distribution', 'compact' => true]),
            ],
            'insights' => [
                $this->insight('Collection health', $this->percent($collectionRate), 'Paid against selected-period invoices', 'fa-circle-check'),
                $this->insight('Overdue exposure', $this->money($balances['overdue']), 'Balance overdue at period end', 'fa-clock-rotate-left'),
                $this->insight('Leading payment method', $leadingMethod ?: 'No payments', $leadingMethod ? $paymentMethods->get($leadingMethod).' transaction(s)' : 'No collected payments', 'fa-credit-card'),
                $this->insight('Largest open balance', $this->money($largestBalance), 'Single outstanding invoice', 'fa-file-invoice-dollar'),
            ],
            'aging' => $aging,
            'largest' => $invoiceRows->where('balance', '>', 0)->sortByDesc('balance')->take(10)->values()->all(),
            'payments' => $payments->sortByDesc('paid_at')->map(fn ($payment) => [
                'date' => $this->localTimestamp($payment->paid_at)->format('Y-m-d H:i'),
                'invoice' => $this->invoiceNumber($payment->invoice_id),
                'patient' => trim($payment->first_name.' '.$payment->last_name) ?: 'Unknown patient',
                'method' => $payment->method ?: 'Unspecified', 'reference' => $payment->reference ?: '',
                'amount' => (float) $payment->amount, 'receiver' => $payment->receiver_name ?: 'Unassigned',
            ])->values()->all(),
            'invoices' => $selectedInvoiceRows->all(),
        ];
    }

    private function appointmentReport(ReportDateRange $range, Collection $appointments, Collection $previousAppointments, Collection $dentists): array
    {
        $completion = $this->appointmentRate($appointments, $range->to, 'completed');
        $previousCompletion = $this->appointmentRate($previousAppointments, $range->previousTo, 'completed');
        $cancellation = $this->appointmentRate($appointments, $range->to, 'cancelled');
        $previousCancellation = $this->appointmentRate($previousAppointments, $range->previousTo, 'cancelled');
        $clinicDays = max(1, $range->days());
        $status = $this->counts($appointments, 'status', ['pending', 'confirmed', 'completed', 'cancelled']);
        $weekdays = collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->mapWithKeys(fn ($day) => [$day => $appointments->filter(fn ($a) => $a['at']->format('l') === $day)->count()]);
        $windows = collect(['Morning' => 0, 'Afternoon' => 0, 'Evening' => 0]);
        foreach ($appointments as $appointment) {
            $hour = (int) $appointment['at']->format('G');
            $window = $hour < 12 ? 'Morning' : ($hour < 17 ? 'Afternoon' : 'Evening');
            $windows->put($window, $windows->get($window) + 1);
        }
        $workload = $dentists->map(fn ($dentist) => $this->workloadRow($dentist->name, $appointments->where('dentist_id', $dentist->id)))->values();
        $peakWeekday = $weekdays->sortDesc()->keys()->first();
        $peakWindow = $windows->sortDesc()->keys()->first();
        $unassigned = $appointments->whereNull('dentist_id')->count();

        return [
            'kpis' => [
                $this->kpi('Total appointments', $appointments->count(), 'number', $previousAppointments->count()),
                $this->kpi('Completed', $appointments->where('status', 'completed')->count(), 'number', $previousAppointments->where('status', 'completed')->count()),
                $this->kpi('Cancelled', $appointments->where('status', 'cancelled')->count(), 'number', $previousAppointments->where('status', 'cancelled')->count()),
                $this->kpi('Completion rate', $completion, 'percent', $previousCompletion, true),
                $this->kpi('Cancellation rate', $cancellation, 'percent', $previousCancellation, true),
                $this->kpi('Average per clinic day', $appointments->count() / $clinicDays, 'decimal', $previousAppointments->count() / $clinicDays),
                $this->kpi('Unassigned', $appointments->whereNull('dentist_id')->count(), 'number', $previousAppointments->whereNull('dentist_id')->count()),
            ],
            'charts' => [
                'appointments-trend' => $this->trendChart('Appointment volume', $range, $appointments, fn ($a) => $a['at'], fn () => 1, 'Appointments', 'number', ['layout' => 'wide', 'variant' => 'trend']),
                'appointments-status' => $this->distributionChart('Status distribution', $status, 'Appointments', 'doughnut', 'number', ['layout' => 'compact', 'variant' => 'distribution', 'compact' => true]),
                'appointments-weekday' => $this->distributionChart('Weekday demand', $weekdays, 'Appointments', 'bar', 'number', ['layout' => 'half', 'variant' => 'ranking', 'orientation' => 'horizontal']),
                'appointments-window' => $this->distributionChart('Time-window demand', $windows, 'Appointments', 'bar', 'number', ['layout' => 'half', 'variant' => 'ranking', 'orientation' => 'horizontal', 'compact' => true]),
            ],
            'insights' => [
                $this->insight('Peak weekday', $appointments->count() ? $peakWeekday : 'No demand yet', $appointments->count() ? $weekdays->get($peakWeekday).' appointment(s)' : 'No appointments in this period', 'fa-calendar-week'),
                $this->insight('Peak time window', $appointments->count() ? $peakWindow : 'No demand yet', $appointments->count() ? $windows->get($peakWindow).' appointment(s)' : 'No appointments in this period', 'fa-clock'),
                $this->insight('Unassigned workload', number_format($unassigned), $unassigned ? 'Needs dentist assignment' : 'All appointments assigned', 'fa-user-clock'),
            ],
            'workload' => $workload->all(),
            'rows' => $appointments->sortBy('at')->map(fn ($appointment) => [
                'schedule' => $appointment['at']->format('Y-m-d H:i'), 'patient' => $appointment['patient'],
                'dentist' => $appointment['dentist'] ?: 'Unassigned', 'services' => $appointment['services'],
                'duration' => $appointment['duration'], 'status' => ucfirst($appointment['status']),
            ])->values()->all(),
        ];
    }

    private function patientsServices(ReportDateRange $range, Collection $appointments, Collection $previousAppointments, Collection $records, Collection $previousRecords, Collection $patients, Collection $newPatients, Collection $previousNewPatients, Collection $priorPatientIds, Collection $previousPriorPatientIds): array
    {
        $activeIds = $appointments->pluck('patient_id')->merge($records->pluck('patient_id'))->filter()->unique();
        $returningIds = $activeIds->intersect($priorPatientIds);
        $previousActiveIds = $previousAppointments->pluck('patient_id')->merge($previousRecords->pluck('patient_id'))->filter()->unique();
        $previousReturningIds = $previousActiveIds->intersect($previousPriorPatientIds);
        $booked = $appointments->flatMap(fn ($a) => $a['service_names'])->countBy();
        $treatment = $records->groupBy(fn ($r) => $r->procedure ?: 'Unspecified');
        $services = $booked->keys()->merge($treatment->keys())->unique()->map(function ($service) use ($booked, $treatment, $records) {
            $serviceRecords = $treatment->get($service, collect());
            $sessions = $serviceRecords->count();
            $fees = (float) $serviceRecords->sum('treatment_fee');

            return ['service' => $service, 'booked' => (int) $booked->get($service, 0), 'sessions' => $sessions, 'share' => $this->percent($records->count() ? $sessions / $records->count() * 100 : 0), 'fees' => $fees, 'average_fee' => $sessions ? $fees / $sessions : 0];
        })->sortByDesc('sessions')->values();
        $activePatients = $patients->whereIn('id', $activeIds);
        $ageBands = collect(['Under 18' => 0, '18–29' => 0, '30–44' => 0, '45–59' => 0, '60+' => 0, 'Unknown' => 0]);
        foreach ($activePatients as $patient) {
            $age = $patient->dob ? (int) $patient->dob->diffInYears($range->to) : null;
            $band = $age === null ? 'Unknown' : ($age < 18 ? 'Under 18' : ($age < 30 ? '18–29' : ($age < 45 ? '30–44' : ($age < 60 ? '45–59' : '60+'))));
            $ageBands->put($band, $ageBands->get($band) + 1);
        }
        $gender = $activePatients->countBy(fn ($p) => $p->gender ? ucfirst($p->gender) : 'Not recorded');
        $missingDemographics = $activePatients->filter(fn ($patient) => ! $patient->dob || ! $patient->gender)->count();
        $patientRows = $activePatients->map(function ($patient) use ($appointments, $records) {
            $patientRecords = $records->where('patient_id', $patient->id);

            return ['identifier' => 'PAT-'.str_pad((string) $patient->id, 4, '0', STR_PAD_LEFT), 'name' => $patient->name, 'registered' => $patient->created_at->setTimezone(ReportDateRange::TIMEZONE)->format('Y-m-d'), 'status' => ucfirst($patient->status), 'appointments' => $appointments->where('patient_id', $patient->id)->count(), 'treatments' => $patientRecords->count(), 'fees' => (float) $patientRecords->sum('treatment_fee')];
        })->sortBy('name')->values();

        return [
            'kpis' => [
                $this->kpi('New patients', $newPatients->count(), 'number', $previousNewPatients->count()),
                $this->kpi('Returning patients', $returningIds->count(), 'number', $previousReturningIds->count()),
                $this->kpi('Treatment sessions', $records->count(), 'number', $previousRecords->count()),
                $this->kpi('Distinct treated patients', $records->pluck('patient_id')->filter()->unique()->count(), 'number', $previousRecords->pluck('patient_id')->filter()->unique()->count()),
                $this->kpi('Recorded treatment fees', $records->sum('treatment_fee'), 'currency', $previousRecords->sum('treatment_fee')),
                $this->kpi('Average treatment fee', $records->count() ? $records->sum('treatment_fee') / $records->count() : 0, 'currency', $previousRecords->count() ? $previousRecords->sum('treatment_fee') / $previousRecords->count() : 0),
            ],
            'charts' => [
                'patients-growth' => $this->trendChart('Patient growth', $range, $newPatients, fn ($p) => $this->localTimestamp($p->created_at), fn () => 1, 'New patients', 'number', ['layout' => 'wide', 'variant' => 'trend']),
                'patients-new-returning' => $this->distributionChart('New versus returning patients', collect(['New' => $newPatients->count(), 'Returning' => $returningIds->count()]), 'Patients', 'doughnut', 'number', ['layout' => 'compact', 'variant' => 'distribution', 'compact' => true]),
                'patients-age' => $this->distributionChart('Age bands', $ageBands, 'Patients', 'bar', 'number', ['layout' => 'half', 'variant' => 'ranking', 'orientation' => 'horizontal']),
                'patients-gender' => $this->distributionChart('Gender distribution', $gender, 'Patients', 'doughnut', 'number', ['layout' => 'half', 'variant' => 'distribution']),
                'services-demand' => $this->groupedChart('Service demand and completed sessions', $services->pluck('service')->all(), [
                    ['label' => 'Booked appointments', 'data' => $services->pluck('booked')->all(), 'format' => 'number'],
                    ['label' => 'Completed sessions', 'data' => $services->pluck('sessions')->all(), 'format' => 'number'],
                ], ['layout' => 'full', 'variant' => 'comparison', 'orientation' => 'horizontal', 'empty' => 'No booked services or completed treatments in this period.']),
            ],
            'insights' => [
                $this->insight('Returning-patient share', $this->percent($activeIds->count() ? $returningIds->count() / $activeIds->count() * 100 : 0), 'Of patients active in this period', 'fa-user-check'),
                $this->insight('Leading booked service', $booked->sortDesc()->keys()->first() ?: 'No booked services', $booked->sum().' booked service selection(s)', 'fa-calendar-plus'),
                $this->insight('Leading completed treatment', $treatment->map->count()->sortDesc()->keys()->first() ?: 'No completed treatments', $records->count().' treatment session(s)', 'fa-tooth'),
                $this->insight('Missing demographics', number_format($missingDemographics), $missingDemographics ? 'Active patients missing age or gender' : 'Active patient profiles complete', 'fa-address-card'),
            ],
            'services' => $services->all(),
            'patients' => $patientRows->all(),
        ];
    }

    private function dentistReport(ReportDateRange $range, Collection $appointments, Collection $records, Collection $dentists): array
    {
        $rows = $dentists->map(function ($dentist) use ($appointments, $records, $range) {
            $assigned = $appointments->where('dentist_id', $dentist->id);
            $treatments = $records->where('dentist_id', $dentist->id);
            $sessions = $treatments->count();
            $fees = (float) $treatments->sum('treatment_fee');

            return [
                'dentist' => $dentist->name, 'status' => ucfirst($dentist->status), 'assigned' => $assigned->count(),
                'completed' => $assigned->where('status', 'completed')->count(), 'cancelled' => $assigned->where('status', 'cancelled')->count(),
                'completion_rate' => $this->percent($this->appointmentRate($assigned, $range->to, 'completed')),
                'sessions' => $sessions, 'patients' => $treatments->pluck('patient_id')->filter()->unique()->count(),
                'fees' => $fees, 'average_fee' => $sessions ? $fees / $sessions : 0,
            ];
        })->sortByDesc('sessions')->values();
        $unassigned = $appointments->whereNull('dentist_id');
        $leadingSessions = $rows->sortByDesc('sessions')->first();
        $eligibleCompletion = $rows->filter(fn ($row) => $row['assigned'] > 0)->sortByDesc(fn ($row) => (float) rtrim($row['completion_rate'], '%'))->first();
        $leadingFees = $rows->sortByDesc('fees')->first();

        return [
            'kpis' => [
                $this->snapshotKpi('Dentists represented', $rows->count(), 'number', 'Active plus dentists with activity'),
                $this->snapshotKpi('Completed appointments', $rows->sum('completed'), 'number', 'Selected period'),
                $this->snapshotKpi('Treatment sessions', $rows->sum('sessions'), 'number', 'Selected period'),
                $this->snapshotKpi('Recorded treatment fees', $rows->sum('fees'), 'currency', 'Not collected revenue'),
            ],
            'rows' => $rows->all(),
            'unassigned' => ['assigned' => $unassigned->count(), 'completed' => $unassigned->where('status', 'completed')->count(), 'cancelled' => $unassigned->where('status', 'cancelled')->count()],
            'charts' => [
                'dentists-activity' => $this->groupedChart('Dentist workload and clinical activity', $rows->pluck('dentist')->all(), [
                    ['label' => 'Assigned appointments', 'data' => $rows->pluck('assigned')->all(), 'format' => 'number'],
                    ['label' => 'Completed appointments', 'data' => $rows->pluck('completed')->all(), 'format' => 'number'],
                    ['label' => 'Treatment sessions', 'data' => $rows->pluck('sessions')->all(), 'format' => 'number'],
                ], ['layout' => 'full', 'variant' => 'comparison', 'orientation' => 'horizontal', 'empty' => 'No dentist workload or clinical activity in this period.']),
            ],
            'insights' => [
                $this->insight('Leading treatment activity', $leadingSessions['dentist'] ?? 'No dentist activity', isset($leadingSessions) ? $leadingSessions['sessions'].' session(s)' : 'No recorded sessions', 'fa-tooth'),
                $this->insight('Highest completion rate', $eligibleCompletion['dentist'] ?? 'No eligible dentist', $eligibleCompletion['completion_rate'] ?? 'No elapsed assigned appointments', 'fa-circle-check'),
                $this->insight('Leading recorded fees', $leadingFees['dentist'] ?? 'No fee activity', isset($leadingFees) ? $this->money($leadingFees['fees']).' recorded fees' : 'No recorded treatment fees', 'fa-receipt'),
                $this->insight('Unassigned appointments', number_format($unassigned->count()), $unassigned->count() ? 'Needs dentist assignment' : 'All work assigned', 'fa-user-clock'),
            ],
        ];
    }

    private function appointmentsFor(ReportDateRange $range): Collection
    {
        return Appointment::query()->with('serviceItems')
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->leftJoin('users as dentists', 'dentists.id', '=', 'appointments.dentist_id')
            ->where(fn (Builder $query) => $this->whereEffectiveBetween($query, $range->previousFrom, $range->to, 'appointments.'))
            ->get([
                'appointments.id', 'appointments.patient_id', 'appointments.dentist_id', 'appointments.full_name',
                'appointments.status', 'appointments.service', 'appointments.duration_minutes', 'appointments.scheduled_start_at',
                'appointments.requested_start_at', 'appointments.preferred_date', 'appointments.appointment_date',
                'patients.first_name', 'patients.last_name', 'dentists.name as dentist_name',
            ])->map(function (Appointment $appointment) {
                $serviceNames = $appointment->serviceItems->isNotEmpty() ? $appointment->serviceItems->pluck('name_snapshot') : collect([$appointment->service ?: 'Unspecified']);

                return [
                    'id' => $appointment->id, 'patient_id' => $appointment->patient_id, 'dentist_id' => $appointment->dentist_id,
                    'patient' => trim($appointment->first_name.' '.$appointment->last_name) ?: $appointment->full_name,
                    'dentist' => $appointment->dentist_name, 'status' => strtolower($appointment->status),
                    'services' => $serviceNames->join(', '), 'service_names' => $serviceNames->all(),
                    'duration' => $appointment->total_duration_minutes, 'at' => $this->effectiveAt($appointment),
                ];
            });
    }

    private function invoiceRow($invoice, ReportDateRange $range): array
    {
        $paid = min((float) $invoice->paid_to, (float) $invoice->total);
        $balance = max((float) $invoice->total - $paid, 0);
        $days = $invoice->due_date ? $this->localDate($invoice->due_date)->diffInDays($range->to->startOfDay(), false) : 0;
        $aging = $balance <= 0 || ! $invoice->due_date || $days <= 0 ? 'Not due' : ($days <= 30 ? '1–30 days' : ($days <= 60 ? '31–60 days' : ($days <= 90 ? '61–90 days' : '90+ days')));
        $status = $balance <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid');

        return [
            'invoice' => $this->invoiceNumber($invoice->id), 'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
            'due_date' => $invoice->due_date?->format('Y-m-d') ?: '', 'patient' => trim($invoice->first_name.' '.$invoice->last_name) ?: 'Unknown patient',
            'subtotal' => (float) $invoice->subtotal, 'discount' => (float) $invoice->discount, 'total' => (float) $invoice->total,
            'paid' => $paid, 'balance' => $balance, 'status' => $status, 'aging' => $aging,
        ];
    }

    private function balances(Collection $invoices, CarbonImmutable $asOf, string $paidColumn): array
    {
        $eligible = $invoices->filter(fn ($invoice) => $this->localDate($invoice->invoice_date)->lte($asOf));
        $outstanding = 0.0;
        $overdue = 0.0;
        foreach ($eligible as $invoice) {
            $balance = max((float) $invoice->total - (float) $invoice->{$paidColumn}, 0);
            $outstanding += $balance;
            if ($balance > 0 && $invoice->due_date && $this->localDate($invoice->due_date)->lt($asOf->startOfDay())) {
                $overdue += $balance;
            }
        }

        return compact('outstanding', 'overdue');
    }

    private function workloadRow(string $name, Collection $appointments): array
    {
        $elapsed = $appointments->filter(fn ($a) => $a['at']->lte(CarbonImmutable::now(ReportDateRange::TIMEZONE)));

        return [
            'dentist' => $name, 'assigned' => $appointments->count(), 'completed' => $appointments->where('status', 'completed')->count(),
            'cancelled' => $appointments->where('status', 'cancelled')->count(), 'pending' => $appointments->where('status', 'pending')->count(),
            'duration' => $appointments->sum('duration'), 'completion_rate' => $this->percent($elapsed->count() ? $elapsed->where('status', 'completed')->count() / $elapsed->count() * 100 : 0),
        ];
    }

    private function appointmentRate(Collection $appointments, CarbonImmutable $periodEnd, string $status): float
    {
        $cutoff = min($periodEnd->timestamp, CarbonImmutable::now(ReportDateRange::TIMEZONE)->timestamp);
        $elapsed = $appointments->filter(fn ($appointment) => $appointment['at']->timestamp <= $cutoff);

        return $elapsed->count() ? $elapsed->where('status', $status)->count() / $elapsed->count() * 100 : 0;
    }

    private function kpi(string $label, float|int $value, string $format, float|int $previous, bool $points = false): array
    {
        $change = $points ? (float) $value - (float) $previous : ((float) $previous == 0 ? ((float) $value > 0 ? null : 0) : ((float) $value - (float) $previous) / abs((float) $previous) * 100);
        $comparison = $change === null ? '↑ New' : (($change > 0 ? '↑ ' : ($change < 0 ? '↓ ' : '→ ')).number_format(abs($change), 1).($points ? ' pp' : '%'));

        return ['label' => $label, 'value' => $this->format($value, $format), 'raw' => (float) $value, 'format' => $format, 'comparison' => $comparison, 'direction' => $change <=> 0, 'context' => 'vs previous equal period'];
    }

    private function snapshotKpi(string $label, float|int $value, string $format, string $context): array
    {
        return ['label' => $label, 'value' => $this->format($value, $format), 'raw' => (float) $value, 'format' => $format, 'comparison' => null, 'direction' => 0, 'context' => $context];
    }

    private function trendChart(string $title, ReportDateRange $range, Collection $items, callable $date, callable $value, string $label, string $format = 'number', array $presentation = []): array
    {
        return $this->dualTrendChart($title, $range, [['label' => $label, 'items' => $items, 'date' => $date, 'value' => $value, 'format' => $format]], $presentation);
    }

    private function dualTrendChart(string $title, ReportDateRange $range, array $series, array $presentation = []): array
    {
        $buckets = $this->buckets($range);
        $datasets = collect($series)->map(function ($seriesItem) use ($buckets) {
            $values = collect($buckets)->mapWithKeys(fn ($bucket) => [$bucket['key'] => 0.0]);
            foreach ($seriesItem['items'] as $item) {
                $at = $seriesItem['date']($item);
                foreach ($buckets as $bucket) {
                    if ($at->betweenIncluded($bucket['from'], $bucket['to'])) {
                        $values[$bucket['key']] += $seriesItem['value']($item);
                        break;
                    }
                }
            }

            return ['label' => $seriesItem['label'], 'data' => $values->values()->all(), 'format' => $seriesItem['format']];
        })->all();

        return $this->chart('line', $title, collect($buckets)->pluck('label')->all(), $datasets, $presentation);
    }

    private function distributionChart(string $title, Collection $counts, string $label, string $type = 'doughnut', string $format = 'number', array $presentation = []): array
    {
        return $this->chart($type, $title, $counts->keys()->map(fn ($key) => ucfirst((string) $key))->all(), [['label' => $label, 'data' => $counts->values()->all(), 'format' => $format]], $presentation);
    }

    private function groupedChart(string $title, array $labels, array $datasets, array $presentation = []): array
    {
        return $this->chart('bar', $title, $labels, $datasets, $presentation);
    }

    private function chart(string $type, string $title, array $labels, array $datasets, array $presentation = []): array
    {
        $parts = [];
        foreach ($labels as $index => $label) {
            $values = collect($datasets)->map(fn ($dataset) => $dataset['label'].': '.$this->format($dataset['data'][$index] ?? 0, $dataset['format']))->join(', ');
            $parts[] = $label.' — '.$values;
        }

        return [
            'type' => $type,
            'title' => $title,
            'labels' => $labels,
            'datasets' => $datasets,
            'summary' => $parts ? implode('; ', $parts).'.' : 'No data in the selected period.',
            'layout' => $presentation['layout'] ?? 'half',
            'orientation' => $presentation['orientation'] ?? ($type === 'bar' ? 'horizontal' : 'vertical'),
            'variant' => $presentation['variant'] ?? ($type === 'line' ? 'trend' : 'distribution'),
            'format' => $datasets[0]['format'] ?? 'number',
            'compact' => (bool) ($presentation['compact'] ?? false),
            'empty' => $presentation['empty'] ?? 'No data in the selected period.',
        ];
    }

    private function insight(string $label, string $value, string $context, string $icon): array
    {
        return compact('label', 'value', 'context', 'icon');
    }

    private function buckets(ReportDateRange $range): array
    {
        $unit = $range->days() <= 31 ? 'day' : ($range->days() <= 120 ? 'week' : 'month');
        $cursor = $range->from->startOfDay();
        $buckets = [];
        while ($cursor->lte($range->to)) {
            $end = match ($unit) {
                'day' => $cursor->endOfDay(), 'week' => $cursor->addDays(6)->endOfDay(), default => $cursor->endOfMonth()
            };
            $end = $end->min($range->to);
            $buckets[] = ['key' => $cursor->toDateString(), 'from' => $cursor, 'to' => $end, 'label' => $unit === 'day' ? $cursor->format('M j') : ($unit === 'week' ? $cursor->format('M j').'–'.$end->format('M j') : $cursor->format('M Y'))];
            $cursor = $end->addSecond()->startOfDay();
        }

        return $buckets;
    }

    private function counts(Collection $items, string $field, array $include = [], string $fallback = 'Unspecified'): Collection
    {
        $counts = $items->countBy(fn ($item) => data_get($item, $field) ?: $fallback);
        foreach ($include as $key) {
            if (! $counts->has($key)) {
                $counts[$key] = 0;
            }
        }

        return $counts;
    }

    private function rangeMeta(ReportDateRange $range): array
    {
        return ['period' => $range->period, 'from' => $range->from->toDateString(), 'to' => $range->to->toDateString(), 'label' => $range->label(), 'previous_label' => $range->previousLabel(), 'bucket' => $range->days() <= 31 ? 'daily' : ($range->days() <= 120 ? 'weekly' : 'monthly')];
    }

    private function whereEffectiveBetween(Builder $query, CarbonImmutable $from, CarbonImmutable $to, string $prefix = ''): void
    {
        $query->where(fn (Builder $q) => $q->whereNotNull($prefix.'scheduled_start_at')->whereBetween($prefix.'scheduled_start_at', [$from->setTimezone('UTC'), $to->setTimezone('UTC')]))
            ->orWhere(fn (Builder $q) => $q->whereNull($prefix.'scheduled_start_at')->whereNotNull($prefix.'requested_start_at')->whereBetween($prefix.'requested_start_at', [$from->setTimezone('UTC'), $to->setTimezone('UTC')]))
            ->orWhere(fn (Builder $q) => $q->whereNull($prefix.'scheduled_start_at')->whereNull($prefix.'requested_start_at')->whereNotNull($prefix.'preferred_date')->whereDate($prefix.'preferred_date', '>=', $from->toDateString())->whereDate($prefix.'preferred_date', '<=', $to->toDateString()))
            ->orWhere(fn (Builder $q) => $q->whereNull($prefix.'scheduled_start_at')->whereNull($prefix.'requested_start_at')->whereNull($prefix.'preferred_date')->whereDate($prefix.'appointment_date', '>=', $from->toDateString())->whereDate($prefix.'appointment_date', '<=', $to->toDateString()));
    }

    private function whereEffectiveBefore(Builder $query, CarbonImmutable $before): void
    {
        $query->where(fn (Builder $q) => $q->whereNotNull('scheduled_start_at')->where('scheduled_start_at', '<', $before->setTimezone('UTC')))
            ->orWhere(fn (Builder $q) => $q->whereNull('scheduled_start_at')->whereNotNull('requested_start_at')->where('requested_start_at', '<', $before->setTimezone('UTC')))
            ->orWhere(fn (Builder $q) => $q->whereNull('scheduled_start_at')->whereNull('requested_start_at')->whereNotNull('preferred_date')->whereDate('preferred_date', '<', $before->toDateString()))
            ->orWhere(fn (Builder $q) => $q->whereNull('scheduled_start_at')->whereNull('requested_start_at')->whereNull('preferred_date')->whereDate('appointment_date', '<', $before->toDateString()));
    }

    private function effectiveAt(Appointment $appointment): CarbonImmutable
    {
        if ($appointment->scheduled_start_at) {
            return CarbonImmutable::instance($appointment->scheduled_start_at)->setTimezone(ReportDateRange::TIMEZONE);
        }
        if ($appointment->requested_start_at) {
            return CarbonImmutable::instance($appointment->requested_start_at)->setTimezone(ReportDateRange::TIMEZONE);
        }

        return $this->localDate($appointment->preferred_date ?? $appointment->appointment_date);
    }

    private function localTimestamp($date): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->setTimezone(ReportDateRange::TIMEZONE);
    }

    private function localDate($date): CarbonImmutable
    {
        return CarbonImmutable::parse($date->format('Y-m-d'), ReportDateRange::TIMEZONE)->startOfDay();
    }

    private function dateIn($date, CarbonImmutable $from, CarbonImmutable $to): bool
    {
        return $this->localDate($date)->betweenIncluded($from, $to);
    }

    private function invoiceNumber(int $id): string
    {
        return 'INV-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function money(float $value): string
    {
        return '₱'.number_format($value, 2);
    }

    private function percent(float $value): string
    {
        return number_format($value, 1).'%';
    }

    private function format(float|int $value, string $format): string
    {
        return match ($format) {
            'currency' => $this->money((float) $value), 'percent' => $this->percent((float) $value), 'decimal' => number_format((float) $value, 1), default => number_format((float) $value)
        };
    }
}
