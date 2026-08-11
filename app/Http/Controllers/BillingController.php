<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Services\BillingEmailDispatcher;
use App\Support\DomainCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->select(['id', 'patient_id', 'invoice_date', 'due_date', 'total', 'payment_status'])
            ->with(['patient:id,first_name,last_name', 'items:id,invoice_id,description'])->withSum('payments', 'amount')
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

        $summary = Cache::remember(DomainCache::key('billing', 'summary'), 30, function (): array {
            $paidByInvoice = Payment::query()
                ->selectRaw('invoice_id, SUM(amount) as paid_amount')
                ->groupBy('invoice_id');
            $totals = Invoice::query()
                ->leftJoinSub($paidByInvoice, 'payment_totals', 'payment_totals.invoice_id', '=', 'invoices.id')
                ->selectRaw('COALESCE(SUM(invoices.total), 0) as total')
                ->selectRaw('COALESCE(SUM(COALESCE(payment_totals.paid_amount, 0)), 0) as paid')
                ->selectRaw('COALESCE(SUM(invoices.total - COALESCE(payment_totals.paid_amount, 0)), 0) as unpaid')
                ->selectRaw('COALESCE(SUM(CASE WHEN invoices.due_date < ? AND invoices.payment_status != ? THEN invoices.total - COALESCE(payment_totals.paid_amount, 0) ELSE 0 END), 0) as overdue', [today()->toDateString(), 'paid'])
                ->first();

            return collect($totals->toArray())->map(fn ($value) => (float) $value)->all();
        });

        return view('billing.index', [
            'invoices' => $invoices,
            'summary' => $summary,
            'services' => Service::cached(),
        ]);
    }

    public function create()
    {
        return view('billing.create', [
            'services' => Service::cached(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', Rule::exists('patients', 'id')->where('status', 'active')],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'discount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'initial_payment_amount' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0'],
            'payment_method' => ['nullable', 'required_with:initial_payment_amount', Rule::in(self::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $subtotal = round(collect($data['items'])->sum(fn ($item) => $item['qty'] * $item['price']), 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        $total = round(max($subtotal - $discount, 0), 2);

        if ($subtotal > 99999999.99) {
            throw ValidationException::withMessages(['items' => 'The invoice subtotal is too large.']);
        }

        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'The discount cannot exceed the subtotal.']);
        }

        $initialPayment = round((float) ($data['initial_payment_amount'] ?? 0), 2);
        if ($initialPayment > $total) {
            throw ValidationException::withMessages(['initial_payment_amount' => 'The payment cannot exceed the invoice total.']);
        }

        $invoice = DB::transaction(function () use ($data, $subtotal, $discount, $total, $initialPayment) {
            $invoice = Invoice::create([
                'patient_id' => $data['patient_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_status' => 'unpaid',
                'payment_method' => $initialPayment > 0 ? $data['payment_method'] : null,
                'notes' => $data['notes'] ?? null,
            ]);

            $timestamp = now();
            InvoiceItem::insert(collect($data['items'])->map(fn ($item) => [
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->all());

            if ($initialPayment > 0) {
                $invoice->payments()->create([
                    'amount' => $initialPayment,
                    'method' => $data['payment_method'],
                    'reference' => $data['payment_reference'] ?? null,
                    'paid_at' => now(),
                    'received_by' => auth()->id(),
                ]);
                $invoice->syncPaymentStatus();
            }

            return $invoice;
        });

        app(BillingEmailDispatcher::class)->queue(
            $invoice,
            $invoice->payment_status === 'paid' ? 'receipt' : 'invoice',
        );

        return $this->respond($request, redirect()->route('billing.receipt', $invoice)->with('status', 'Invoice created successfully.'));
    }

    public function receipt(Invoice $invoice)
    {
        $invoice->load(['patient', 'items', 'payments.receiver', 'emailDeliveries', 'dentalRecord.dentist']);

        return view('billing.receipt', [
            'invoice' => $invoice,
            'clinic' => ClinicSetting::current(),
        ]);
    }

    public function recordPayment(Request $request, Invoice $invoice, BillingEmailDispatcher $emails)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'method' => ['required', Rule::in(self::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $invoice = DB::transaction(function () use ($data, $invoice) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $balance = $locked->balance;

            $amount = round((float) $data['amount'], 2);
            if ($amount > round($balance, 2)) {
                throw ValidationException::withMessages(['amount' => 'The payment cannot exceed the remaining balance.']);
            }

            $locked->payments()->create([
                ...$data,
                'amount' => $amount,
                'received_by' => auth()->id(),
            ]);
            $locked->syncPaymentStatus();

            return $locked->fresh();
        });

        $emails->queue($invoice, $invoice->payment_status === 'paid' ? 'receipt' : 'invoice');

        return back()->with('status', $invoice->payment_status === 'paid'
            ? 'Payment recorded. Receipt queued for email.'
            : 'Payment recorded. Updated invoice queued for email.');
    }

    public function sendDocument(Invoice $invoice, BillingEmailDispatcher $emails)
    {
        $type = $invoice->payment_status === 'paid' ? 'receipt' : 'invoice';
        $emails->queue($invoice, $type, 'manual');

        return back()->with('status', ucfirst($type).' queued for email.');
    }

    private const PAYMENT_METHODS = ['Cash', 'GCash', 'Maya', 'Credit/Debit Card', 'PhilHealth', 'Bank Transfer', 'Other'];
}
