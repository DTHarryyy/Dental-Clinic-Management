<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'dob', 'gender', 'civil_status', 'occupation',
        'mobile', 'email', 'address', 'emergency_contact_name', 'emergency_contact_number',
        'allergies', 'medications', 'conditions', 'notes', 'status',
    ];

    protected $casts = [
        'conditions' => 'array',
        'dob' => 'date',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function dentalRecords()
    {
        return $this->hasMany(DentalRecord::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->dob?->age;
    }
}
