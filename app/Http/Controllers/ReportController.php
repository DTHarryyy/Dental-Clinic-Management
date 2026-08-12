<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Services\ClinicReportService;
use App\Support\ReportDateRange;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ReportController extends Controller
{
    public const TABS = ['overview', 'financial', 'appointments', 'patients-services', 'dentists'];

    public function index(Request $request, ClinicReportService $reports)
    {
        $range = $this->rangeFrom($request);
        if ($range instanceof InvalidArgumentException) {
            return redirect()->route('reports')->withErrors(['date_range' => $range->getMessage()]);
        }

        $tab = in_array($request->string('tab')->toString(), self::TABS, true)
            ? $request->string('tab')->toString()
            : 'overview';

        return view('reports.index', [
            'report' => $reports->report($range, $tab),
            'range' => $range,
            'clinic' => ClinicSetting::current(),
            'activeTab' => $tab,
            'tabs' => self::TABS,
            'generatedAt' => CarbonImmutable::now(ReportDateRange::TIMEZONE),
        ]);
    }

    public function pdf(Request $request, ClinicReportService $reports)
    {
        $range = $this->rangeFrom($request);
        if ($range instanceof InvalidArgumentException) {
            return redirect()->route('reports')->withErrors(['date_range' => $range->getMessage()]);
        }

        $contents = Pdf::loadView('reports.pdf', [
            'report' => $reports->report($range, full: true),
            'range' => $range,
            'clinic' => ClinicSetting::current(),
            'generatedAt' => CarbonImmutable::now(ReportDateRange::TIMEZONE),
        ])->setPaper('a4', 'landscape')->output();

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename('clinic-performance', $range, 'pdf').'"',
        ]);
    }

    public function csv(Request $request, string $dataset, ClinicReportService $reports)
    {
        abort_unless(in_array($dataset, ClinicReportService::CSV_DATASETS, true), 404);
        $range = $this->rangeFrom($request);
        if ($range instanceof InvalidArgumentException) {
            return redirect()->route('reports')->withErrors(['date_range' => $range->getMessage()]);
        }
        [$headers, $rows] = $reports->csvRows($dataset, $range);

        return response()->streamDownload(function () use ($headers, $rows): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($output, array_map([$this, 'safeSpreadsheetValue'], $row), ',', '"', '');
            }
            fclose($output);
        }, $this->filename($dataset, $range, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function rangeFrom(Request $request): ReportDateRange|InvalidArgumentException
    {
        $legacyCustom = ! $request->filled('period') && ($request->filled('from') || $request->filled('to'));
        try {
            return ReportDateRange::fromInput(
                $legacyCustom ? 'custom' : $request->string('period', 'this_month')->toString(),
                $request->string('from')->toString() ?: null,
                $request->string('to')->toString() ?: null,
            );
        } catch (InvalidArgumentException $exception) {
            return $exception;
        }
    }

    private function filename(string $prefix, ReportDateRange $range, string $extension): string
    {
        return "{$prefix}-{$range->from->toDateString()}-to-{$range->to->toDateString()}.{$extension}";
    }

    public function safeSpreadsheetValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[\s]*[=+\-@]/u', $value)) {
            return "'{$value}";
        }

        return $value;
    }
}
