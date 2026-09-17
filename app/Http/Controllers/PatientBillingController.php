<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PatientBillingController extends Controller
{
    private const STATUSES = ['all', 'unpaid', 'partial', 'paid'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : 'all';

        $patient = $request->user()->patient;
        $invoices = $patient->invoices()
            ->with(['items', 'payments'])
            ->withSum('payments', 'amount')
            ->when($status !== 'all', fn ($query) => $query->where('payment_status', $status))
            ->latest('invoice_date')
            ->paginate(8)
            ->withQueryString();

        $all = $patient->invoices()->withSum('payments', 'amount')->get(['id', 'total', 'payment_status', 'due_date']);

        return view('patient.billing.index', [
            'invoices' => $invoices,
            'summary' => [
                'outstanding' => $all->sum->balance,
                'paid' => $all->where('payment_status', 'paid')->sum('total'),
                'overdue' => $all->filter(fn ($invoice) => $invoice->display_status === 'Overdue')->sum->balance,
            ],
            'status' => $status,
        ]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $invoice = $request->user()->patient->invoices()
            ->with(['items', 'payments.receiver:id,name', 'dentalRecord.dentist:id,name'])
            ->whereKey($invoice->id)
            ->firstOrFail();

        return view('patient.billing.show', compact('invoice'));
    }

    public function receipt(Request $request, Invoice $invoice)
    {
        $invoice = $request->user()->patient->invoices()
            ->with(['patient', 'items', 'payments.receiver', 'emailDeliveries', 'dentalRecord.dentist'])
            ->whereKey($invoice->id)
            ->firstOrFail();

        return view('patient.billing.receipt', [
            'invoice' => $invoice,
            'clinic' => ClinicSetting::current(),
        ]);
    }
}
