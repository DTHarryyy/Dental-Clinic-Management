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
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function dentalRecord()
    {
        return $this->belongsTo(DentalRecord::class);
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
