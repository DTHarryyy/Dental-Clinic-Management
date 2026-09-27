<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the session's user from cache instead of the database. Every authenticated
 * request starts with this lookup, and against the remote Supabase database it is a full
 * round trip (~90ms) on pages that otherwise need no query at all.
 *
 * Freshness: User::saved/deleted forget the entry (see User::booted()), so role, status and
 * password changes apply on the very next request — EnsureActiveStaff and friends still see
 * the current state. The TTL only bounds staleness from writes that bypass Eloquent.
 */
class CachedEloquentUserProvider extends EloquentUserProvider
{
    public const TTL_SECONDS = 600;

    public static function cacheKey(int|string $id): string
    {
        return "auth:user:{$id}";
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        // Raw attributes, not the model: every request gets a fresh instance, so relations
        // loaded during one request can never ride along into the next.
        $attributes = Cache::get(self::cacheKey($identifier));

        if (is_array($attributes)) {
            return $this->createModel()->newFromBuilder($attributes);
        }

        $user = parent::retrieveById($identifier);

        if ($user) {
            Cache::put(self::cacheKey($identifier), $user->getAttributes(), self::TTL_SECONDS);
        }

        return $user;
    }
}
