<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientConsent extends Model
{
    protected $fillable = [
        'user_id', 'type', 'version', 'accepted_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
