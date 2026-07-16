<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalRecord extends Model
{
    protected $fillable = [
        'patient_id', 'dentist_id', 'treatment_date', 'procedure', 'tooth_area',
        'next_appointment_date', 'clinical_notes', 'prescription', 'treatment_fee',
    ];

    protected $casts = [
        'treatment_date' => 'date',
        'next_appointment_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
