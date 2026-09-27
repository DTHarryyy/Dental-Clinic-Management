<?php

namespace App\Models;

use App\Auth\CachedEloquentUserProvider;
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
        'patient_id',
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
        return $this->statusEnum() === UserStatus::Active && ($this->roleEnum()?->isStaff() ?? false);
    }

    public function isActivePatient(): bool
    {
        return $this->statusEnum() === UserStatus::Active
            && $this->roleEnum() === Role::Patient
            && $this->email_verified_at !== null;
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

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Unread count, memoized for the request: the patient layout, the patient dashboard and
     * the notifications page each need it for the same render (3 remote round trips before).
     */
    public function unreadNotificationCount(): int
    {
        // Keyed on the request, not the model: the same User instance can outlive a request
        // (tests reuse it across calls), and a count must never leak into the next one.
        $key = 'unread_notification_count:'.$this->getKey();
        $attributes = request()->attributes;

        if (! $attributes->has($key)) {
            $attributes->set($key, $this->unreadNotifications()->count());
        }

        return $attributes->get($key);
    }

    /** Inline the dentist's name into a list query — see Patient::inlineNameSelects(). */
    public static function inlineDentistSelect(string $qualifiedForeignKey): array
    {
        return ['inline_dentist_name' => static::select('name')->whereColumn('users.id', $qualifiedForeignKey)];
    }

    /** Set the `dentist` relation (id + name only) from the inlineDentistSelect() column. */
    public static function attachInlineDentist(\Illuminate\Database\Eloquent\Model $model): void
    {
        $name = $model->getAttributes()['inline_dentist_name'] ?? null;
        $model->setRelation('dentist', $model->dentist_id && $name !== null
            ? (new static)->newFromBuilder(['id' => $model->dentist_id, 'name' => $name])
            : null);
        unset($model->inline_dentist_name);
    }

    public const DENTISTS_CACHE_KEY = 'users:dentists';

    protected static function booted(): void
    {
        // Any meaningful create/update/delete invalidates the cached dentist list.
        static::saved(function (self $user) {
            if ($user->affectsSharedCaches()) {
                static::forgetDentistsCache();
            }
        });
        static::deleted(fn () => static::forgetDentistsCache());

        // The session user is served from cache (CachedEloquentUserProvider); any write to
        // the account must be visible on the very next request.
        static::saved(fn (self $user) => Cache::forget(CachedEloquentUserProvider::cacheKey($user->getKey())));
        static::deleted(fn (self $user) => Cache::forget(CachedEloquentUserProvider::cacheKey($user->getKey())));
    }

    /**
     * Columns rewritten by routine auth activity (login with "remember me", logout, a
     * password change) that nothing cached ever reads. Saving only these must not flush
     * the dentist list or the dashboard/report caches — every login did, before.
     */
    private const CACHE_IRRELEVANT_COLUMNS = ['remember_token', 'password', 'must_change_password', 'updated_at'];

    public function affectsSharedCaches(): bool
    {
        $changed = array_keys($this->getChanges());

        // wasRecentlyCreated stays true for the instance's lifetime; the insert itself is the
        // save that has it set with no recorded changes.
        return ($this->wasRecentlyCreated && $changed === [])
            || array_diff($changed, self::CACHE_IRRELEVANT_COLUMNS) !== [];
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
