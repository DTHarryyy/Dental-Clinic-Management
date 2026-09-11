<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentChangeRequest extends Model
{
    protected $fillable = [
        'appointment_id', 'patient_user_id', 'patient_id', 'type', 'reason',
        'original_start_at', 'original_end_at', 'proposed_start_at', 'proposed_end_at',
        'status', 'resolved_by_user_id', 'resolution_note', 'resolved_at',
    ];

    protected $casts = [
        'original_start_at' => 'datetime',
        'original_end_at' => 'datetime',
        'proposed_start_at' => 'datetime',
        'proposed_end_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patientUser()
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
