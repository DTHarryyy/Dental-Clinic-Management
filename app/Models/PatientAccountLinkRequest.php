<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAccountLinkRequest extends Model
{
    protected $fillable = [
        'user_id', 'normalized_email', 'candidate_count', 'status',
        'resolved_by_user_id', 'selected_patient_id', 'note', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function selectedPatient()
    {
        return $this->belongsTo(Patient::class, 'selected_patient_id');
    }
}
