<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DomainCache
{
    public static function key(string $namespace, string $suffix): string
    {
        return "{$namespace}:v".self::version($namespace).":{$suffix}";
    }

    public static function version(string $namespace): int
    {
        return (int) Cache::get("cache-version:{$namespace}", 1);
    }

    public static function bump(string ...$namespaces): void
    {
        foreach (array_unique($namespaces) as $namespace) {
            $key = "cache-version:{$namespace}";
            Cache::add($key, 1);
            Cache::increment($key);
        }
    }

    public static function bumpAfterCommit(string ...$namespaces): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => self::bump(...$namespaces));

            return;
        }

        self::bump(...$namespaces);
    }
}
