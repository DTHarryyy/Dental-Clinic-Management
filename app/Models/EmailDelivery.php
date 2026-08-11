<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailDelivery extends Model
{
    protected $fillable = ['event_type', 'related_type', 'related_id', 'recipient', 'status', 'idempotency_key', 'provider_message_id', 'error', 'attempts', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function related()
    {
        return $this->morphTo();
    }
}
