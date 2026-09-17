<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PublicTeamProfile extends Model
{
    protected $fillable = [
        'user_id', 'name', 'title', 'specialties', 'biography',
        'photo_path', 'display_order', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public const CACHE_KEY = 'team-profiles:all';

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Every team profile, display-order first — read by both the public site and Settings. */
    public static function cached()
    {
        return Cache::memo()->rememberForever(
            self::CACHE_KEY,
            fn () => static::with('user:id,name')->orderBy('display_order')->orderBy('name')->get()
        );
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::memo()->forget(self::CACHE_KEY);
    }
}
