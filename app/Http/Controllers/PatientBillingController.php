<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PatientBillingController extends Controller
{
    public function index(Request $request)
    {
        $patient = $request->user()->patient;
        $invoices = $patient->invoices()
            ->with(['items', 'payments'])
            ->withSum('payments', 'amount')
            ->when($request->status && $request->status !== 'all', fn ($query) => $query->where('payment_status', $request->status))
            ->latest('invoice_date')
            ->paginate(8)
            ->withQueryString();

        $all = $patient->invoices()->with('payments')->get();

        return view('patient.billing.index', [
            'invoices' => $invoices,
            'summary' => [
                'outstanding' => $all->sum->balance,
                'paid' => $all->where('payment_status', 'paid')->sum('total'),
                'overdue' => $all->filter(fn ($invoice) => $invoice->display_status === 'Overdue')->sum->balance,
            ],
            'status' => $request->status ?: 'all',
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
