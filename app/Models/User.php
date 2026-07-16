<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'license_no',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public const DENTISTS_CACHE_KEY = 'users:dentists';

    protected static function booted(): void
    {
        // Any create/update/delete invalidates the cached dentist list automatically.
        static::saved(fn () => static::forgetDentistsCache());
        static::deleted(fn () => static::forgetDentistsCache());
    }

    /** Cached, name-ordered dentist list for form dropdowns. */
    public static function cachedDentists()
    {
        return Cache::rememberForever(self::DENTISTS_CACHE_KEY, fn () => static::where('role', 'dentist')->orderBy('name')->get());
    }

    public static function forgetDentistsCache(): void
    {
        Cache::forget(self::DENTISTS_CACHE_KEY);
    }
}
