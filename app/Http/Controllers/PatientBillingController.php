<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ClinicPaymentChannel;
use App\Models\ClinicSetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentSubmissionNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientBillingController extends Controller
{
    private const STATUSES = ['all', 'unpaid', 'partial', 'paid'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : 'all';

        $patient = $request->user()->patient;
        $invoices = $patient->invoices()
            ->with(['items', 'payments'])
            ->withSum('verifiedPayments', 'amount')
            ->withSum('pendingPayments', 'amount')
            ->when($status !== 'all', fn ($query) => $query->where('payment_status', $status))
            ->latest('invoice_date')
            ->paginate(8)
            ->withQueryString();

        $all = $patient->invoices()
            ->withSum('verifiedPayments', 'amount')
            ->withSum('pendingPayments', 'amount')
            ->get(['id', 'total', 'payment_status', 'due_date']);

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
            ->withSum('verifiedPayments', 'amount')
            ->withSum('pendingPayments', 'amount')
            ->whereKey($invoice->id)
            ->firstOrFail();

        $activeMethods = ClinicPaymentChannel::activeMethods();

        return view('patient.billing.show', [
            'invoice' => $invoice,
            'paymentChannels' => ClinicPaymentChannel::enabled(),
            'submittableMethods' => array_values(array_filter($activeMethods, fn (PaymentMethod $m) => $m->isPatientSubmittable())),
            'inPersonMethods' => array_values(array_filter($activeMethods, fn (PaymentMethod $m) => ! $m->isPatientSubmittable())),
        ]);
    }

    public function receipt(Request $request, Invoice $invoice)
    {
        $invoice = $request->user()->patient->invoices()
            ->with(['patient', 'items', 'verifiedPayments.receiver', 'emailDeliveries', 'dentalRecord.dentist'])
            ->whereKey($invoice->id)
            ->firstOrFail();

        return view('patient.billing.receipt', [
            'invoice' => $invoice,
            'clinic' => ClinicSetting::current(),
        ]);
    }

    public function submitPayment(Request $request, Invoice $invoice, PaymentSubmissionNotifier $notifier)
    {
        $invoice = $request->user()->patient->invoices()->whereKey($invoice->id)->firstOrFail();

        abort_if($invoice->payment_status === 'paid', 403, 'This invoice is already settled.');

        $submittable = collect(ClinicPaymentChannel::activeMethods())
            ->filter(fn (PaymentMethod $method) => $method->isPatientSubmittable())
            ->map->value->all();

        $data = $request->validate([
            'method' => ['required', Rule::in($submittable)],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'reference' => [
                'required', 'string', 'max:255',
            ],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $payment = DB::transaction(function () use ($data, $invoice, $request) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($locked->pendingPayments()->exists()) {
                throw ValidationException::withMessages([
                    'amount' => 'You already have a payment awaiting verification for this invoice.',
                ]);
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount > round($locked->submittable_balance, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'The amount cannot exceed the remaining balance of PHP '.number_format($locked->submittable_balance, 2).'.',
                ]);
            }

            if (Payment::where('invoice_id', $locked->id)
                ->where('reference', $data['reference'])
                ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Verified->value])
                ->exists()) {
                throw ValidationException::withMessages([
                    'reference' => 'This reference number has already been submitted for this invoice.',
                ]);
            }

            $proofPath = $request->hasFile('proof')
                ? $request->file('proof')->storeAs(
                    'invoice-'.$locked->id,
                    str()->uuid().'.'.$request->file('proof')->extension(),
                    'proofs',
                )
                : null;

            return $locked->payments()->create([
                'amount' => $amount,
                'method' => $data['method'],
                'status' => PaymentStatus::Pending,
                'reference' => $data['reference'],
                'proof_path' => $proofPath,
                'paid_at' => $data['paid_at'],
                'submitted_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $notifier->notifyStaff($payment);

        return $this->respond($request, redirect()
            ->route('patient.billing.show', $invoice)
            ->with('status', 'Payment submitted. Our staff will verify it shortly.'));
    }
}
