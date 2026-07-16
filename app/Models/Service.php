<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Service extends Model
{
    protected $fillable = ['name', 'price', 'duration'];

    public const CACHE_KEY = 'services:all';

    protected static function booted(): void
    {
        // Any create/update/delete invalidates the cached catalog automatically,
        // so every form + the create-time validation always sees the live list.
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    /** Cached, name-ordered catalog — the single source of truth for services. */
    public static function cached()
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::orderBy('name')->get());
    }

    /** Cached list of service names — used by form dropdowns and create-time validation. */
    public static function names()
    {
        return static::cached()->pluck('name');
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
