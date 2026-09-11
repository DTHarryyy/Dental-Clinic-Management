<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ClinicSetting extends Model
{
    protected $fillable = [
        'clinic_name', 'phone', 'email', 'address', 'tax_id', 'website',
        'booking_lead_minutes', 'booking_horizon_days', 'slot_interval_minutes',
    ];

    protected $casts = [
        'booking_lead_minutes' => 'integer',
        'booking_horizon_days' => 'integer',
        'slot_interval_minutes' => 'integer',
    ];

    public const CACHE_KEY = 'clinic-settings';

    public static function current(): self
    {
        return Cache::memo()->rememberForever(self::CACHE_KEY, fn () => static::query()->firstOrCreate([]));
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::memo()->forget(self::CACHE_KEY);
    }
}
