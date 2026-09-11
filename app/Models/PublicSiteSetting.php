<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PublicSiteSetting extends Model
{
    protected $fillable = [
        'hero_eyebrow', 'hero_title', 'hero_subtitle', 'hero_image_path',
        'about_heading', 'about_body', 'benefits', 'map_embed_url',
        'facebook_url', 'instagram_url', 'meta_title', 'meta_description',
        'og_image_path', 'privacy_policy', 'terms',
        'privacy_policy_version', 'terms_version',
    ];

    protected $casts = [
        'benefits' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    public static function current(): self
    {
        return Cache::rememberForever('public_site_settings:current', fn () => static::firstOrCreate([], [
            'hero_title' => 'Aquilizan Dental Clinic',
            'hero_subtitle' => 'Friendly, dental care with verified online booking.',
            'about_heading' => 'Care that feels organized from hello to follow-up.',
            'about_body' => 'Our clinic combines attentive dental care with a secure patient portal for appointments, billing, and published aftercare summaries.',
            'benefits' => ['Verified patient booking', 'Clear billing records', 'Dentist-approved aftercare summaries'],
            'privacy_policy' => 'We collect only the personal and dental-care information needed to manage appointments, treatment, billing, and clinic communications.',
            'terms' => 'Use of the portal is limited to your own account and dental-care information. Appointment requests are subject to clinic confirmation.',
            'privacy_policy_version' => 'privacy-v1',
            'terms_version' => 'terms-v1',
        ]));
    }

    public static function forgetCache(): void
    {
        Cache::forget('public_site_settings:current');
    }
}
