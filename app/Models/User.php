<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Support\PermissionMatrix;
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
        'supabase_uid',
        'password',
        'role',
        'phone',
        'license_no',
        'status',
        'must_change_password',
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
            'must_change_password' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->roleEnum() === Role::Admin;
    }

    /** Centralized record-writing permission used by mixed appointment views. */
    public function canWriteRecords(): bool
    {
        return $this->hasPermission(Permission::RecordsCreate);
    }

    public function roleEnum(): ?Role
    {
        return Role::tryFrom((string) $this->role);
    }

    public function statusEnum(): ?UserStatus
    {
        return UserStatus::tryFrom((string) $this->status);
    }

    public function isActiveStaff(): bool
    {
        return $this->statusEnum() === UserStatus::Active && $this->roleEnum() !== null;
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $permission = is_string($permission) ? Permission::tryFrom($permission) : $permission;
        $role = $this->roleEnum();

        return $permission !== null
            && $role !== null
            && $this->statusEnum() === UserStatus::Active
            && PermissionMatrix::allows($role, $permission);
    }

    public const DENTISTS_CACHE_KEY = 'users:dentists';

    protected static function booted(): void
    {
        // Any create/update/delete invalidates the cached dentist list automatically.
        static::saved(fn () => static::forgetDentistsCache());
        static::deleted(fn () => static::forgetDentistsCache());
    }

    /**
     * Cached, name-ordered dentist list for form dropdowns. Active only - these dropdowns
     * assign a dentist to *new* work, and a deactivated account should not be assignable.
     * Past appointments and records keep their dentist_id regardless.
     */
    public static function cachedDentists()
    {
        return Cache::memo()->rememberForever(
            self::DENTISTS_CACHE_KEY,
            fn () => static::where('role', 'dentist')->where('status', 'active')->orderBy('name')->get()
        );
    }

    public static function forgetDentistsCache(): void
    {
        Cache::forget(self::DENTISTS_CACHE_KEY);
        Cache::memo()->forget(self::DENTISTS_CACHE_KEY);
    }
}
