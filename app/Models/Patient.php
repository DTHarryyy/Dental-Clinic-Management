<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Patient extends Model
{
    use HasFactory;

    public const DROPDOWN_CACHE_KEY = 'patients:dropdown';

    public const INDEX_CACHE_VERSION_KEY = 'patients:index:version';

    protected $fillable = [
        'first_name', 'last_name', 'dob', 'gender', 'civil_status', 'occupation',
        'mobile', 'email', 'address', 'emergency_contact_name', 'emergency_contact_number',
        'allergies', 'medications', 'conditions', 'notes', 'status',
    ];

    protected $casts = [
        'conditions' => 'array',
        'dob' => 'date',
    ];

    protected static function booted(): void
    {
        // Any create/update/delete invalidates the cached dropdown list automatically.
        static::saved(fn () => static::invalidateCachesAfterCommit());
        static::deleted(fn () => static::invalidateCachesAfterCommit());
    }

    /**
     * Cached, name-ordered list for form dropdowns — avoids a remote DB round trip per page.
     * Active only: these dropdowns pick who to create *new* work for, and deactivating a
     * patient is how the clinic says "don't book, bill, or treat this person any more".
     * Their existing appointments, records, and invoices are unaffected.
     */
    public static function dropdown()
    {
        return Cache::rememberForever(
            self::DROPDOWN_CACHE_KEY,
            fn () => static::where('status', 'active')->orderBy('first_name')->get()
        );
    }

    public static function forgetDropdownCache(): void
    {
        Cache::forget(self::DROPDOWN_CACHE_KEY);
    }

    public static function invalidateCachesAfterCommit(): void
    {
        $invalidate = function (): void {
            static::forgetDropdownCache();
            static::bumpIndexCacheVersion();
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($invalidate);

            return;
        }

        $invalidate();
    }

    /**
     * Bumping this invalidates every cached patients-index result in one write, without having
     * to enumerate the (search/status/gender/page) key combinations that produced them — see
     * PatientController::index(). Called whenever a Patient, DentalRecord, or Invoice changes,
     * since all three feed that list (name/status columns, last_visit, and the view dialog).
     */
    public static function bumpIndexCacheVersion(): void
    {
        if (! Cache::add(self::INDEX_CACHE_VERSION_KEY, 1)) {
            Cache::increment(self::INDEX_CACHE_VERSION_KEY);
        }
    }

    public static function bumpIndexCacheVersionAfterCommit(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => static::bumpIndexCacheVersion());

            return;
        }

        static::bumpIndexCacheVersion();
    }

    public static function indexCacheVersion(): int
    {
        return (int) Cache::get(self::INDEX_CACHE_VERSION_KEY, 0);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function dentalRecords()
    {
        return $this->hasMany(DentalRecord::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->dob?->age;
    }
}
