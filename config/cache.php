<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Database Fingerprint
|--------------------------------------------------------------------------
|
| Everything this app caches is derived from the database - the dentist and
| patient dropdowns, the service catalog, clinic settings, the dashboard and
| report figures. None of it is namespaced by which database it came from, so
| a process pointed somewhere else (a local SQLite run, a staging connection)
| writes its rows under the exact keys the live app reads back. The dropdown
| caches are rememberForever with event-based invalidation, so nothing evicts
| them: a model event can't fire for data that lives in another database.
| Supabase is the source of truth, and this makes it impossible for the cache
| to hold anything else - each database gets its own partition.
|
| The prefix covers the database/redis/memcached stores. The file store ignores
| prefixes entirely (FileStore::getPrefix() returns ''), so it has to be
| partitioned by path instead - and file is what this app actually runs on.
|
*/

$dbFingerprint = Str::slug((string) env('DB_CONNECTION', 'pgsql')).'-'.substr(sha1(implode('|', [
    (string) env('DB_CONNECTION', 'pgsql'),
    (string) env('DB_HOST', ''),
    (string) env('DB_PORT', ''),
    (string) env('DB_DATABASE', ''),
])), 0, 8);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane",
    |                    "failover", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data/'.$dbFingerprint),
            'lock_path' => storage_path('framework/cache/data/'.$dbFingerprint),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    | The database fingerprint is part of the prefix so that two connections
    | sharing one cache server can never read each other's rows. The file store
    | is partitioned by path above instead, since it ignores this prefix.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'.$dbFingerprint.'-'),

];
