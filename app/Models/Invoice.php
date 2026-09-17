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
        return (float) ($this->verified_payments_sum_amount ?? $this->verifiedPayments()->sum('amount'));
    }

    /** Money the patient has claimed to send but staff has not confirmed. Never reduces balance. */
    public function getAmountPendingAttribute(): float
    {
        return (float) ($this->pending_payments_sum_amount ?? $this->pendingPayments()->sum('amount'));
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
        $payments = $this->relationLoaded('payments')
            ? $this->payments->where('status', PaymentStatus::Verified)
            : $this->verifiedPayments()->get(['method']);

        return $payments->pluck('method')->unique()->implode(' + ') ?: null;
    }
}
