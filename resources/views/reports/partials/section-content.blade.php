@php
    $money = fn ($value) => '₱'.number_format((float) $value, 2);
@endphp

@if ($tab === 'financial')
    <div class="grid gap-5 xl:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="mb-4 font-bold text-slate-800">Receivables aging</h3>
            @include('reports.partials.ranked-summary', [
                'items' => collect($section['aging'])->map(fn ($row) => ['label' => $row['bucket'], 'raw' => $row['balance'], 'value' => $money($row['balance']), 'context' => $row['invoices'].' invoice(s)'])->all(),
                'empty' => 'No receivables as of the period end.',
            ])
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="mb-4 font-bold text-slate-800">Largest outstanding invoices</h3>
            @include('reports.partials.responsive-table', [
                'headers' => ['Invoice', 'Patient', 'Due', 'Status', 'Balance'],
                'rows' => collect($section['largest'])->map(fn ($row) => [$row['invoice'], $row['patient'], $row['due_date'] ?: 'No due date', $row['status'], $money($row['balance'])])->all(),
                'empty' => 'No outstanding invoices as of the period end.',
            ])
        </article>
    </div>
    <article class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h3 class="mb-4 font-bold text-slate-800">Payment transactions</h3>
        @include('reports.partials.responsive-table', [
            'headers' => ['Paid at', 'Invoice', 'Patient', 'Method', 'Reference', 'Amount', 'Receiver'],
            'rows' => collect($section['payments'])->map(fn ($row) => [$row['date'], $row['invoice'], $row['patient'], $row['method'], $row['reference'] ?: '—', $money($row['amount']), $row['receiver']])->all(),
            'empty' => 'No collected payments in this period.',
        ])
    </article>
@elseif ($tab === 'appointments')
    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h3 class="mb-4 font-bold text-slate-800">Dentist workload</h3>
        @include('reports.partials.responsive-table', [
            'headers' => ['Dentist', 'Assigned', 'Completed', 'Cancelled', 'Pending', 'Duration', 'Completion'],
            'rows' => collect($section['workload'])->map(fn ($row) => [$row['dentist'], $row['assigned'], $row['completed'], $row['cancelled'], $row['pending'], number_format($row['duration'] / 60, 1).' hrs', $row['completion_rate']])->all(),
            'empty' => 'No dentist workload in this period.',
        ])
    </article>
    <article class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h3 class="mb-4 font-bold text-slate-800">Appointment detail</h3>
        @include('reports.partials.responsive-table', [
            'headers' => ['Effective schedule', 'Patient', 'Dentist', 'Services', 'Duration', 'Status'],
            'rows' => collect($section['rows'])->map(fn ($row) => [$row['schedule'], $row['patient'], $row['dentist'], $row['services'], $row['duration'].' min', $row['status']])->all(),
            'empty' => 'No appointments in this period.',
        ])
    </article>
@elseif ($tab === 'patients-services')
    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h3 class="mb-1 font-bold text-slate-800">Service performance</h3>
        <p class="mb-4 text-xs text-slate-500">Recorded treatment fees are clinical record values, not collected revenue.</p>
        @include('reports.partials.responsive-table', [
            'headers' => ['Service', 'Booked', 'Sessions', 'Share', 'Recorded fees', 'Average fee'],
            'rows' => collect($section['services'])->map(fn ($row) => [$row['service'], $row['booked'], $row['sessions'], $row['share'], $money($row['fees']), $money($row['average_fee'])])->all(),
            'empty' => 'No booked services or completed treatments in this period.',
        ])
    </article>
    <article class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h3 class="mb-4 font-bold text-slate-800">Active patients</h3>
        @include('reports.partials.responsive-table', [
            'headers' => ['Patient', 'Registered', 'Status', 'Appointments', 'Treatments', 'Recorded fees'],
            'rows' => collect($section['patients'])->map(fn ($row) => [$row['name'].' ('.$row['identifier'].')', $row['registered'], $row['status'], $row['appointments'], $row['treatments'], $money($row['fees'])])->all(),
            'empty' => 'No patient activity in this period.',
        ])
    </article>
@elseif ($tab === 'dentists')
    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="mb-4">
            <h3 class="font-bold text-slate-800">Dentist performance</h3>
            <p class="mt-1 text-xs text-slate-500">Includes active dentists and inactive dentists with activity. Recorded fees are not collected revenue.</p>
        </div>
        @include('reports.partials.responsive-table', [
            'headers' => ['Dentist', 'Status', 'Assigned', 'Completed', 'Cancelled', 'Completion', 'Sessions', 'Patients', 'Recorded fees', 'Avg fee'],
            'rows' => collect($section['rows'])->map(fn ($row) => [$row['dentist'], $row['status'], $row['assigned'], $row['completed'], $row['cancelled'], $row['completion_rate'], $row['sessions'], $row['patients'], $money($row['fees']), $money($row['average_fee'])])->all(),
            'empty' => 'No dentists or dentist activity in this period.',
        ])
        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
            <strong class="text-slate-800">Unassigned work:</strong> {{ $section['unassigned']['assigned'] }} appointments, {{ $section['unassigned']['completed'] }} completed, {{ $section['unassigned']['cancelled'] }} cancelled.
        </div>
    </article>
@endif
