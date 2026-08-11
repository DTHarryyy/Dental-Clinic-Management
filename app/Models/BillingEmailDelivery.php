<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingEmailDelivery extends Model
{
    protected $fillable = [
        'invoice_id', 'idempotency_key', 'document_type', 'recipient', 'trigger',
        'status', 'provider_message_id', 'error', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
