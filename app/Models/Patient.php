<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Patient extends Model
{
    use HasFactory;

    public const DROPDOWN_CACHE_KEY = 'patients:dropdown';

    protected $fillable = [
        'first_name', 'last_name', 'dob', 'gender', 'civil_status', 'occupation',
        'mobile', 'email', 'address', 'emergency_contact_name', 'emergency_contact_number',
        'allergies', 'medications', 'conditions', 'notes', 'status',
    ];

    protected $casts = [
        'conditions' => 'array',
        'dob' => 'date',
    ];

    protected static function booted(): void
    {
        // Any create/update/delete invalidates the cached dropdown list automatically.
        static::saved(fn () => static::forgetDropdownCache());
        static::deleted(fn () => static::forgetDropdownCache());
    }

    /** Cached, name-ordered list for form dropdowns — avoids a remote DB round trip per page. */
    public static function dropdown()
    {
        return Cache::rememberForever(self::DROPDOWN_CACHE_KEY, fn () => static::orderBy('first_name')->get());
    }

    public static function forgetDropdownCache(): void
    {
        Cache::forget(self::DROPDOWN_CACHE_KEY);
    }

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
