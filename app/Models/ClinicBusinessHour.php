<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ClinicBusinessHour extends Model
{
    protected $fillable = [
        'day_of_week', 'is_open', 'morning_opens_at', 'morning_closes_at',
        'afternoon_opens_at', 'afternoon_closes_at',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_open' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    public static function cached()
    {
        return Cache::rememberForever('clinic_business_hours:all', fn () => static::orderBy('day_of_week')->get()->keyBy('day_of_week'));
    }

    public static function forgetCache(): void
    {
        Cache::forget('clinic_business_hours:all');
    }
}
