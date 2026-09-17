<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'display_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const CACHE_KEY = 'faqs:active';

    public const ADMIN_CACHE_KEY = 'faqs:admin';

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    /** Active FAQs, display-order first — the public site's FAQ section. */
    public static function cached()
    {
        return Cache::memo()->rememberForever(
            self::CACHE_KEY,
            fn () => static::where('is_active', true)->orderBy('display_order')->orderBy('question')->get()
        );
    }

    /** Every FAQ (active and inactive alike) — what the Settings "FAQs" tab manages. */
    public static function adminCached()
    {
        return Cache::memo()->rememberForever(
            self::ADMIN_CACHE_KEY,
            fn () => static::orderBy('display_order')->orderBy('question')->get()
        );
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::ADMIN_CACHE_KEY);
        Cache::memo()->forget(self::CACHE_KEY);
        Cache::memo()->forget(self::ADMIN_CACHE_KEY);
    }
}
