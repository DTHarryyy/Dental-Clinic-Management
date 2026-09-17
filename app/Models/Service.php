<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Service extends Model
{
    protected $fillable = [
        'name', 'price', 'duration', 'duration_minutes', 'is_active',
        'public_description', 'public_image_path', 'public_sort_order', 'show_public_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'show_public_price' => 'boolean',
    ];

    public const CACHE_KEY = 'services:all';
    public const ADMIN_CACHE_KEY = 'services:admin';

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
        return Cache::memo()->rememberForever(self::CACHE_KEY, fn () => static::where('is_active', true)->orderBy('name')->get());
    }

    /** Cached list of service names — used by form dropdowns and create-time validation. */
    public static function names()
    {
        return static::cached()->pluck('name');
    }

    /**
     * Active service ids — the validation whitelist for every booking endpoint. Replaces
     * Rule::exists('services', 'id'), which issues one query per submitted id.
     */
    public static function activeIds(): array
    {
        return static::cached()->modelKeys();
    }

    /** The subset of the cached catalog matching the given ids, keyed by id. */
    public static function bookable(array $ids): Collection
    {
        return static::cached()->whereIn('id', array_map('intval', $ids))->keyBy('id');
    }

    /**
     * Every service (active and inactive alike), sort-order first — what the Settings
     * "Services" tab manages. Distinct from cached() above, which is the active-only
     * catalog booking/validation reads.
     */
    public static function adminCached()
    {
        return Cache::memo()->rememberForever(
            self::ADMIN_CACHE_KEY,
            fn () => static::orderBy('public_sort_order')->orderBy('name')->get()
        );
    }

    public static function publicCatalog()
    {
        return Cache::memo()->rememberForever('services:public', fn () => static::where('is_active', true)
            ->orderBy('public_sort_order')
            ->orderBy('name')
            ->get());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::ADMIN_CACHE_KEY);
        Cache::forget('services:public');
        Cache::memo()->forget(self::CACHE_KEY);
        Cache::memo()->forget(self::ADMIN_CACHE_KEY);
        Cache::memo()->forget('services:public');
    }
}
