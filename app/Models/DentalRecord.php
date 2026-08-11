<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DentalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'appointment_id', 'dentist_id', 'treatment_date', 'procedure', 'tooth_area',
        'next_appointment_date', 'clinical_notes', 'prescription', 'treatment_fee',
    ];

    protected $casts = [
        'treatment_date' => 'date',
        'next_appointment_date' => 'date',
    ];

    protected static function booted(): void
    {
        // Feeds last_visit and the view dialog on the patients index — see Patient::bumpIndexCacheVersion().
        static::saved(fn () => Patient::bumpIndexCacheVersionAfterCommit());
        static::deleted(fn () => Patient::bumpIndexCacheVersionAfterCommit());
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
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
