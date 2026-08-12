<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'patient_id', 'dental_record_id', 'invoice_date', 'due_date',
        'subtotal', 'discount', 'total', 'payment_status', 'payment_method', 'notes',
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function emailDeliveries()
    {
        return $this->hasMany(BillingEmailDelivery::class);
    }

    public function getAmountPaidAttribute(): float
    {
        return (float) ($this->payments_sum_amount ?? $this->payments()->sum('amount'));
    }

    public function getBalanceAttribute(): float
    {
        return max((float) $this->total - $this->amount_paid, 0);
    }

    public function syncPaymentStatus(): void
    {
        $paid = $this->payments()->sum('amount');
        $status = $paid <= 0 ? 'unpaid' : ($paid >= (float) $this->total ? 'paid' : 'partial');

        $this->update(['payment_status' => $status]);
    }

    public function getInvoiceNumberAttribute(): string
    {
        return 'INV-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->payment_status === 'unpaid' && $this->due_date && $this->due_date->isPast()) {
            return 'Overdue';
        }

        return ucfirst($this->payment_status);
    }
}
