<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ClinicPaymentChannel extends Model
{
    protected $fillable = [
        'method', 'account_name', 'account_number', 'bank_name', 'qr_path', 'instructions', 'is_enabled',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
        'is_enabled' => 'boolean',
    ];

    public const CACHE_KEY = 'clinic-payment-channels';

    public const ALL_CACHE_KEY = 'clinic-payment-channels:all';

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    /** Enabled channels, keyed by method value — the patient-facing receiving-details lookup. */
    public static function enabled(): Collection
    {
        return Cache::memo()->rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->where('is_enabled', true)->get()->keyBy(fn ($channel) => $channel->method->value)
        );
    }

    /** Every configured channel row (enabled or not), keyed by method value. */
    public static function configured(): Collection
    {
        return Cache::memo()->rememberForever(
            self::ALL_CACHE_KEY,
            fn () => static::query()->get()->keyBy(fn ($channel) => $channel->method->value)
        );
    }

    /**
     * The methods currently offered system-wide — the gate for every "which payment
     * methods can be chosen" list (staff invoice/payment forms, the patient portal).
     * A method with no configured row is treated as active so a newly added
     * PaymentMethod case works out of the box before an admin visits Settings.
     *
     * @return array<int, PaymentMethod>
     */
    public static function activeMethods(): array
    {
        $rows = self::configured();

        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $method) => ! $rows->has($method->value) || $rows->get($method->value)->is_enabled
        ));
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::ALL_CACHE_KEY);
        Cache::memo()->forget(self::CACHE_KEY);
        Cache::memo()->forget(self::ALL_CACHE_KEY);
    }

    public function getQrUrlAttribute(): ?string
    {
        return $this->qr_path ? Storage::disk('public')->url($this->qr_path) : null;
    }
}
