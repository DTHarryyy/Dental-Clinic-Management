<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Clinic Performance Report</title>
    <style>
        @page { margin: 22px 28px 34px; }
        * { box-sizing: border-box; }
        body { color: #1e293b; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        h1 { color: #064e3b; font-size: 20px; margin: 0 0 2px; }
        h2 { border-bottom: 2px solid #10b981; color: #0f172a; font-size: 15px; margin: 0 0 10px; padding-bottom: 4px; }
        h3 { color: #334155; font-size: 10px; margin: 10px 0 5px; }
        p { margin: 2px 0; }
        .header { border-bottom: 1px solid #cbd5e1; margin-bottom: 14px; padding-bottom: 10px; }
        .muted { color: #64748b; }
        .section { page-break-before: always; }
        .section.first { page-break-before: auto; }
        .kpis { display: table; table-layout: fixed; width: 100%; }
        .kpi { border: 1px solid #cbd5e1; display: table-cell; padding: 7px; vertical-align: top; }
        .kpi strong { color: #0f172a; display: block; font-size: 12px; margin: 3px 0; }
        .summary { background: #f8fafc; border: 1px solid #e2e8f0; margin: 8px 0; padding: 6px; }
        table { border-collapse: collapse; margin: 5px 0 10px; width: 100%; }
        th { background: #ecfdf5; color: #065f46; font-size: 7px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #cbd5e1; padding: 4px; vertical-align: top; }
        tr { page-break-inside: avoid; }
        .footer { bottom: -22px; color: #64748b; left: 0; position: fixed; right: 0; text-align: center; }
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>
<div class="footer">Clinic performance · Page <span class="page"></span></div>
<header class="header">
    <h1>{{ $clinic->clinic_name ?: config('app.name') }}</h1>
    <p>{{ $clinic->address }} @if($clinic->phone) · {{ $clinic->phone }} @endif @if($clinic->email) · {{ $clinic->email }} @endif</p>
    <p><strong>Clinic Performance Report</strong> · Selected: {{ $range->label() }} · Comparison: {{ $range->previousLabel() }}</p>
    <p class="muted">Generated {{ $generatedAt->format('M j, Y g:i A') }} Asia/Manila · Financial balances are calculated as of the selected period end.</p>
</header>

@php
    $sections = [
        'overview' => ['title' => 'Overview', 'data' => $report['overview']],
        'financial' => ['title' => 'Financial', 'data' => $report['financial']],
        'appointments' => ['title' => 'Appointments', 'data' => $report['appointments']],
        'patientsServices' => ['title' => 'Patients & Services', 'data' => $report['patientsServices']],
        'dentists' => ['title' => 'Dentists', 'data' => $report['dentists']],
    ];
    $money = fn ($value) => 'PHP '.number_format((float) $value, 2);
@endphp

@foreach ($sections as $key => $item)
<section class="section {{ $loop->first ? 'first' : '' }}">
    <h2>{{ $item['title'] }}</h2>
    <div class="kpis">
        @foreach ($item['data']['kpis'] as $kpi)
            <div class="kpi"><span class="muted">{{ $kpi['label'] }}</span><strong>{{ $kpi['value'] }}</strong><span>{{ $kpi['comparison'] ?: $kpi['context'] }}</span></div>
        @endforeach
    </div>
    @if (! empty($item['data']['insights']))
        <h3>Key insights</h3>
        <table><thead><tr><th>Insight</th><th>Result</th><th>Context</th></tr></thead><tbody>@foreach($item['data']['insights'] as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['value'] }}</td><td>{{ $row['context'] ?? '' }}</td></tr>@endforeach</tbody></table>
    @endif
    @foreach ($item['data']['charts'] ?? [] as $chart)
        <div class="summary"><strong>{{ $chart['title'] }}:</strong> {{ $chart['summary'] }}</div>
    @endforeach

    @if ($key === 'financial')
        <h3>Receivables aging</h3>
        <table><thead><tr><th>Bucket</th><th>Invoices</th><th>Balance</th></tr></thead><tbody>@foreach($item['data']['aging'] as $row)<tr><td>{{ $row['bucket'] }}</td><td>{{ $row['invoices'] }}</td><td>{{ $money($row['balance']) }}</td></tr>@endforeach</tbody></table>
        <h3>Largest outstanding invoices</h3>
        <table><thead><tr><th>Invoice</th><th>Patient</th><th>Due date</th><th>Status</th><th>Balance</th></tr></thead><tbody>@forelse($item['data']['largest'] as $row)<tr><td>{{ $row['invoice'] }}</td><td>{{ $row['patient'] }}</td><td>{{ $row['due_date'] ?: '—' }}</td><td>{{ $row['status'] }}</td><td>{{ $money($row['balance']) }}</td></tr>@empty<tr><td colspan="5">No outstanding invoices.</td></tr>@endforelse</tbody></table>
        <h3>Payment transactions</h3>
        <table><thead><tr><th>Date</th><th>Invoice</th><th>Patient</th><th>Method</th><th>Reference</th><th>Amount</th><th>Receiver</th></tr></thead><tbody>@forelse($item['data']['payments'] as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['invoice'] }}</td><td>{{ $row['patient'] }}</td><td>{{ $row['method'] }}</td><td>{{ $row['reference'] }}</td><td>{{ $money($row['amount']) }}</td><td>{{ $row['receiver'] }}</td></tr>@empty<tr><td colspan="7">No payments.</td></tr>@endforelse</tbody></table>
    @elseif ($key === 'appointments')
        <h3>Dentist workload</h3>
        <table><thead><tr><th>Dentist</th><th>Assigned</th><th>Completed</th><th>Cancelled</th><th>Pending</th><th>Duration</th><th>Completion</th></tr></thead><tbody>@forelse($item['data']['workload'] as $row)<tr><td>{{ $row['dentist'] }}</td><td>{{ $row['assigned'] }}</td><td>{{ $row['completed'] }}</td><td>{{ $row['cancelled'] }}</td><td>{{ $row['pending'] }}</td><td>{{ $row['duration'] }} min</td><td>{{ $row['completion_rate'] }}</td></tr>@empty<tr><td colspan="7">No workload data.</td></tr>@endforelse</tbody></table>
    @elseif ($key === 'patientsServices')
        <h3>Service performance</h3>
        <table><thead><tr><th>Service</th><th>Booked</th><th>Sessions</th><th>Share</th><th>Recorded fees</th><th>Average fee</th></tr></thead><tbody>@forelse($item['data']['services'] as $row)<tr><td>{{ $row['service'] }}</td><td>{{ $row['booked'] }}</td><td>{{ $row['sessions'] }}</td><td>{{ $row['share'] }}</td><td>{{ $money($row['fees']) }}</td><td>{{ $money($row['average_fee']) }}</td></tr>@empty<tr><td colspan="6">No service activity.</td></tr>@endforelse</tbody></table>
    @elseif ($key === 'dentists')
        <h3>Dentist performance</h3>
        <table><thead><tr><th>Dentist</th><th>Status</th><th>Assigned</th><th>Completed</th><th>Cancelled</th><th>Completion</th><th>Sessions</th><th>Patients</th><th>Recorded fees</th><th>Average fee</th></tr></thead><tbody>@forelse($item['data']['rows'] as $row)<tr><td>{{ $row['dentist'] }}</td><td>{{ $row['status'] }}</td><td>{{ $row['assigned'] }}</td><td>{{ $row['completed'] }}</td><td>{{ $row['cancelled'] }}</td><td>{{ $row['completion_rate'] }}</td><td>{{ $row['sessions'] }}</td><td>{{ $row['patients'] }}</td><td>{{ $money($row['fees']) }}</td><td>{{ $money($row['average_fee']) }}</td></tr>@empty<tr><td colspan="10">No dentist activity.</td></tr>@endforelse</tbody></table>
        <p><strong>Unassigned work:</strong> {{ $item['data']['unassigned']['assigned'] }} assigned, {{ $item['data']['unassigned']['completed'] }} completed, {{ $item['data']['unassigned']['cancelled'] }} cancelled.</p>
    @endif
</section>
@endforeach
</body>
</html>
