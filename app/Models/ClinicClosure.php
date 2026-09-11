<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicClosure extends Model
{
    protected $fillable = [
        'closure_date', 'is_full_day', 'starts_at', 'ends_at', 'reason',
    ];

    protected $casts = [
        'closure_date' => 'date',
        'is_full_day' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    public static function forgetCache(): void
    {
        ClinicBusinessHour::forgetCache();
    }
}
