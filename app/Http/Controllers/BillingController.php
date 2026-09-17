<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ClinicPaymentChannel;
use App\Models\ClinicSetting;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Services\BillingEmailDispatcher;
use App\Services\PaymentSubmissionNotifier;
use App\Support\DomainCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->select(['id', 'patient_id', 'invoice_date', 'due_date', 'total', 'payment_status'])
            ->with(['patient:id,first_name,last_name', 'items:id,invoice_id,description'])
            ->withSum('verifiedPayments', 'amount')
            ->withSum('pendingPayments', 'amount')
            ->when($request->search, fn ($q) => $q->whereHas('patient', fn ($q2) => $q2
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")))
            ->when($request->integer('view'), fn ($q) => $q->whereKey($request->integer('view')))
            ->when($request->status && $request->status !== 'All Status', fn ($q) => $q->where('payment_status', strtolower($request->status)))
            ->when($request->month, function ($q) use ($request) {
                $date = \Illuminate\Support\Carbon::createFromFormat('Y-m', $request->month);
                $q->whereYear('invoice_date', $date->year)->whereMonth('invoice_date', $date->month);
            })
            ->latest('invoice_date')
            ->paginate(10)
            ->withQueryString();

        // version-stamped by DomainCache and bumped on every Invoice/Payment write
        // (AppServiceProvider) — the TTL only bounds staleness if a bump were ever missed.
        $summary = Cache::remember(DomainCache::key('billing', 'summary'), 600, function (): array {
            $paidByInvoice = Payment::query()
                ->verified()
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
            'paymentMethods' => ClinicPaymentChannel::activeMethods(),
            'viewInvoiceId' => $request->integer('view') ?: null,
            'pendingPaymentCount' => Payment::pending()->count(),
            'unbilledRecords' => DentalRecord::query()
                ->select(['id', 'patient_id', 'treatment_date', 'procedure', 'treatment_fee'])
                ->whereDoesntHave('invoice')
                ->with('patient:id,first_name,last_name,status')
                ->latest('treatment_date')
                ->limit(20)
                ->get(),
        ]);
    }

    public function create(Request $request)
    {
        $prefillRecord = null;
        if ($request->filled('record')) {
            $prefillRecord = DentalRecord::query()
                ->whereDoesntHave('invoice')
                ->with('patient:id,first_name,last_name,status')
                ->findOrFail($request->integer('record'));
        }

        return view('billing.create', [
            'services' => Service::cached(),
            'prefillRecord' => $prefillRecord,
            'paymentMethods' => ClinicPaymentChannel::activeMethods(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dental_record_id' => ['nullable', 'integer', 'exists:dental_records,id', 'unique:invoices,dental_record_id'],
            // Existing treatment remains billable even if the patient was later marked inactive.
            'patient_id' => ['required', 'exists:patients,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'discount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'initial_payment_amount' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0'],
            'payment_method' => ['nullable', 'required_with:initial_payment_amount', Rule::in(self::activeMethodValues())],
            'payment_reference' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => filled($request->input('initial_payment_amount'))
                    && PaymentMethod::tryFrom((string) $request->input('payment_method'))?->requiresReference()),
            ],
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
            $record = null;
            if (filled($data['dental_record_id'] ?? null)) {
                $record = DentalRecord::query()->lockForUpdate()->findOrFail($data['dental_record_id']);
                if ($record->invoice()->exists()) {
                    throw ValidationException::withMessages(['dental_record_id' => 'This treatment already has an invoice.']);
                }
                if ((int) $record->patient_id !== (int) $data['patient_id']) {
                    throw ValidationException::withMessages(['patient_id' => 'The selected patient does not match this treatment.']);
                }
            }

            $invoice = Invoice::create([
                'patient_id' => $data['patient_id'],
                'dental_record_id' => $record?->id,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_status' => 'unpaid',
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
                    'status' => PaymentStatus::Verified,
                    'reference' => $data['payment_reference'] ?? null,
                    'paid_at' => now(),
                    'received_by' => auth()->id(),
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
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
        $invoice->load(['patient', 'items', 'verifiedPayments.receiver', 'emailDeliveries', 'dentalRecord.dentist']);

        return view('billing.receipt', [
            'invoice' => $invoice,
            'clinic' => ClinicSetting::current(),
            'paymentMethods' => ClinicPaymentChannel::activeMethods(),
        ]);
    }

    public function details(Invoice $invoice)
    {
        $invoice->load([
            'patient', 'items',
            'payments' => fn ($query) => $query->with(['receiver:id,name', 'submitter:id,name'])->latest('created_at'),
            'dentalRecord.dentist:id,name',
        ]);

        return view('billing._details-dialog', [
            'invoice' => $invoice,
            'paymentMethods' => ClinicPaymentChannel::activeMethods(),
        ]);
    }

    public function recordPayment(Request $request, Invoice $invoice, BillingEmailDispatcher $emails)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'method' => ['required', Rule::in(self::activeMethodValues())],
            'reference' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => PaymentMethod::tryFrom((string) $request->input('method'))?->requiresReference()),
            ],
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
                'status' => PaymentStatus::Verified,
                'received_by' => auth()->id(),
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);
            $locked->syncPaymentStatus();

            return $locked->fresh();
        });

        $documentType = $invoice->payment_status === 'paid' ? 'receipt' : 'invoice';
        $delivery = $emails->queue($invoice, $documentType);

        $message = $delivery
            ? ($invoice->payment_status === 'paid'
                ? 'Payment recorded. Receipt queued for email.'
                : 'Payment recorded. Updated invoice queued for email.')
            : 'Payment recorded, but no email was sent because the patient has no valid email address.';

        return $this->respond($request, back()->with('status', $message));
    }

    public function pendingPayments(Request $request)
    {
        $payments = Payment::query()
            ->pending()
            ->with(['invoice:id,patient_id,total', 'invoice.patient:id,first_name,last_name', 'submitter:id,name'])
            ->oldest('created_at')
            ->paginate(15);

        return view('billing.pending-payments', [
            'payments' => $payments,
        ]);
    }

    public function verifyPayment(Payment $payment, BillingEmailDispatcher $emails, PaymentSubmissionNotifier $notifier)
    {
        abort_unless($payment->isPending(), 409, 'This submission has already been reviewed.');

        $invoice = DB::transaction(function () use ($payment) {
            $locked = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
            $fresh = Payment::lockForUpdate()->findOrFail($payment->id);

            if (! $fresh->isPending()) {
                throw ValidationException::withMessages(['payment' => 'This submission has already been reviewed.']);
            }

            // The balance can have moved since the patient submitted — e.g. staff
            // recorded a counter payment in the meantime. Re-check under the lock.
            if (round((float) $fresh->amount, 2) > round($locked->balance, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'This submission now exceeds the remaining balance. Reject it instead.',
                ]);
            }

            $fresh->update([
                'status' => PaymentStatus::Verified,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
                'received_by' => $fresh->received_by ?? auth()->id(),
            ]);
            $locked->syncPaymentStatus();

            return $locked->fresh();
        });

        $emails->queue($invoice, $invoice->payment_status === 'paid' ? 'receipt' : 'invoice');
        $notifier->notifyPatient($payment->fresh());

        return back()->with('status', 'Payment verified.');
    }

    public function rejectPayment(Request $request, Payment $payment, PaymentSubmissionNotifier $notifier)
    {
        abort_unless($payment->isPending(), 409, 'This submission has already been reviewed.');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => PaymentStatus::Rejected,
            'rejection_reason' => $data['rejection_reason'],
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        $notifier->notifyPatient($payment->fresh());

        return back()->with('status', 'Payment submission rejected.');
    }

    public function proof(Payment $payment)
    {
        abort_unless($payment->hasProof(), 404);

        return Storage::disk('proofs')->response($payment->proof_path);
    }

    public function sendDocument(Request $request, Invoice $invoice, BillingEmailDispatcher $emails)
    {
        $type = $invoice->payment_status === 'paid' ? 'receipt' : 'invoice';
        $emails->queue($invoice, $type, 'manual');

        return $this->respond($request, back()->with('status', ucfirst($type).' queued for email.'));
    }

    /** @return array<int, string> The methods an admin has left offered, for validation. */
    private static function activeMethodValues(): array
    {
        return collect(ClinicPaymentChannel::activeMethods())->map->value->all();
    }
}
