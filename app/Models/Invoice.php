<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'patient_id', 'dental_record_id', 'invoice_date', 'due_date',
        'subtotal', 'discount', 'total', 'payment_status', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Feeds the patients index view dialog — see Patient::bumpIndexCacheVersion().
        static::saved(fn () => Patient::bumpIndexCacheVersionAfterCommit());
        static::deleted(fn () => Patient::bumpIndexCacheVersionAfterCommit());
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->select(Patient::BASIC_COLUMNS);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function dentalRecord()
    {
        return $this->belongsTo(DentalRecord::class);
    }

    /** All payments including pending/rejected — display only, never money math. */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /** The only relationship that may be summed for balances/revenue. */
    public function verifiedPayments()
    {
        return $this->hasMany(Payment::class)->where('payments.status', PaymentStatus::Verified->value);
    }

    /** Patient-submitted claims awaiting staff confirmation. */
    public function pendingPayments()
    {
        return $this->hasMany(Payment::class)->where('payments.status', PaymentStatus::Pending->value);
    }

    public function emailDeliveries()
    {
        return $this->hasMany(BillingEmailDelivery::class);
    }

    public function getAmountPaidAttribute(): float
    {
        return $this->paymentTotal('verified_payments_sum_amount', 'verifiedPayments', PaymentStatus::Verified);
    }

    /** Money the patient has claimed to send but staff has not confirmed. Never reduces balance. */
    public function getAmountPendingAttribute(): float
    {
        return $this->paymentTotal('pending_payments_sum_amount', 'pendingPayments', PaymentStatus::Pending);
    }

    /**
     * Sum of one payment status, from whatever the caller already loaded before falling back
     * to a query. withSum() yields NULL — not 0 — for an invoice with no matching payments,
     * so it is the presence of the aggregate column, not its value, that means "already
     * summed". (A `??` fallback here used to re-query every unpaid invoice, per read.)
     */
    private function paymentTotal(string $sumAttribute, string $relation, PaymentStatus $status): float
    {
        if (array_key_exists($sumAttribute, $this->getAttributes())) {
            return (float) $this->getAttributes()[$sumAttribute];
        }

        if ($this->relationLoaded($relation)) {
            return (float) $this->getRelation($relation)->sum('amount');
        }

        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->filter(fn (Payment $payment) => $payment->status === $status)->sum('amount');
        }

        return (float) $this->{$relation}()->sum('amount');
    }

    /** Eager-load everything the balance/status accessors read, in one round trip. */
    public function scopeWithPaymentTotals($query)
    {
        return $query->withSum('verifiedPayments', 'amount')->withSum('pendingPayments', 'amount');
    }

    public function loadPaymentTotals(): static
    {
        return $this->loadSum('verifiedPayments', 'amount')->loadSum('pendingPayments', 'amount');
    }

    public function getBalanceAttribute(): float
    {
        return max((float) $this->total - $this->amount_paid, 0);
    }

    /** Balance a NEW patient submission may not exceed — blocks stacking duplicate pending claims. */
    public function getSubmittableBalanceAttribute(): float
    {
        return max((float) $this->total - $this->amount_paid - $this->amount_pending, 0);
    }

    public function getHasPendingSubmissionAttribute(): bool
    {
        return $this->amount_pending > 0;
    }

    public function syncPaymentStatus(): void
    {
        $paid = $this->verifiedPayments()->sum('amount');
        $status = $paid <= 0 ? 'unpaid' : ($paid >= (float) $this->total ? 'paid' : 'partial');

        $this->update(['payment_status' => $status]);
    }

    public function getInvoiceNumberAttribute(): string
    {
        return 'INV-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->payment_status !== 'paid' && $this->has_pending_submission) {
            return 'Pending verification';
        }

        if ($this->payment_status === 'unpaid' && $this->due_date && $this->due_date->isPast()) {
            return 'Overdue';
        }

        return ucfirst($this->payment_status);
    }

    /**
     * Replaces the dropped denormalized invoices.payment_method column, which only
     * ever recorded the first payment's method. Lists every method actually used.
     */
    public function getPaymentMethodsSummaryAttribute(): ?string
    {
        $payments = match (true) {
            $this->relationLoaded('verifiedPayments') => $this->verifiedPayments,
            $this->relationLoaded('payments') => $this->payments->where('status', PaymentStatus::Verified),
            default => $this->verifiedPayments()->get(['method']),
        };

        return $payments->pluck('method')->unique()->implode(' + ') ?: null;
    }
}
