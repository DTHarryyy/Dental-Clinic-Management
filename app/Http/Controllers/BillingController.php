<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->with(['patient', 'items'])
            ->when($request->search, fn ($q) => $q->whereHas('patient', fn ($q2) => $q2
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")))
            ->when($request->status && $request->status !== 'All Status', fn ($q) => $q->where('payment_status', strtolower($request->status)))
            ->when($request->month, function ($q) use ($request) {
                $date = \Illuminate\Support\Carbon::createFromFormat('Y-m', $request->month);
                $q->whereYear('invoice_date', $date->year)->whereMonth('invoice_date', $date->month);
            })
            ->latest('invoice_date')
            ->paginate(10)
            ->withQueryString();

        $summaryRow = Invoice::selectRaw(
            "SUM(total) as total,
             SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as paid,
             SUM(CASE WHEN payment_status = 'unpaid' THEN total ELSE 0 END) as unpaid,
             SUM(CASE WHEN payment_status = 'unpaid' AND due_date < ? THEN total ELSE 0 END) as overdue",
            [now()]
        )->first();

        $summary = [
            'total' => $summaryRow->total ?? 0,
            'paid' => $summaryRow->paid ?? 0,
            'unpaid' => $summaryRow->unpaid ?? 0,
            'overdue' => $summaryRow->overdue ?? 0,
        ];

        return view('billing.index', [
            'invoices' => $invoices,
            'summary' => $summary,
            'patients' => Patient::dropdown(),
            'services' => Service::cached(),
        ]);
    }

    public function create()
    {
        return view('billing.create', [
            'patients' => Patient::dropdown(),
            'services' => Service::cached(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:unpaid,paid,partial'],
            'payment_method' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $subtotal = collect($data['items'])->sum(fn ($item) => $item['qty'] * $item['price']);
        $discount = $data['discount'] ?? 0;
        $total = max($subtotal - $discount, 0);

        $invoice = DB::transaction(function () use ($data, $subtotal, $discount, $total) {
            $invoice = Invoice::create([
                'patient_id' => $data['patient_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_status' => $data['payment_status'],
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);
            }

            return $invoice;
        });

        return $this->respond($request, redirect()->route('billing.receipt', $invoice)->with('status', 'Invoice created successfully.'));
    }

    public function receipt(Invoice $invoice)
    {
        $invoice->load(['patient', 'items', 'dentalRecord.dentist']);

        return view('billing.receipt', [
            'invoice' => $invoice,
            'clinic' => ClinicSetting::current(),
        ]);
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['payment_status' => 'paid']);

        return back()->with('status', 'Invoice marked as paid.');
    }
}
