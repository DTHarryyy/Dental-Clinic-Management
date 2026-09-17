<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ClinicClosure extends Model
{
    protected $fillable = [
        'closure_date', 'is_full_day', 'starts_at', 'ends_at', 'reason',
    ];

    protected $casts = [
        'closure_date' => 'date',
        'is_full_day' => 'boolean',
    ];

    public const CACHE_KEY = 'clinic_closures:all';

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    /**
     * Every closure, grouped by Y-m-d. The availability grid asks "is this range closed?"
     * once per candidate slot, so reading the whole table once beats a query per slot -
     * it is a handful of rows per year.
     */
    public static function cached()
    {
        return Cache::memo()->rememberForever(self::CACHE_KEY, fn () => static::orderBy('closure_date')
            ->get()
            ->groupBy(fn (self $closure) => $closure->closure_date->toDateString()));
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::memo()->forget(self::CACHE_KEY);
        ClinicBusinessHour::forgetCache();
    }
}
