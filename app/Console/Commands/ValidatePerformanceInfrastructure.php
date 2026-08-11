<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ValidatePerformanceInfrastructure extends Command
{
    protected $signature = 'app:performance-check';

    protected $description = 'Validate application database and Redis connectivity without touching the health endpoint';

    public function handle(): int
    {
        $failed = false;

        $checks = ['Supabase database' => fn () => DB::select('select 1')];

        if (config('cache.default') === 'redis') {
            $checks['Redis cache'] = fn () => Redis::connection('cache')->ping();
        } else {
            $this->components->twoColumnDetail('Redis cache', '<fg=yellow>skipped (CACHE_STORE is not redis)</>');
        }

        if (config('session.driver') === 'redis') {
            $checks['Redis sessions'] = fn () => Redis::connection('session')->ping();
        } else {
            $this->components->twoColumnDetail('Redis sessions', '<fg=yellow>skipped (SESSION_DRIVER is not redis)</>');
        }

        if (config('queue.default') === 'redis') {
            $checks['Redis queues'] = fn () => Redis::connection('queue')->ping();
        } else {
            $this->components->twoColumnDetail('Redis queues', '<fg=yellow>skipped (QUEUE_CONNECTION is not redis)</>');
        }

        foreach ($checks as $name => $check) {
            $started = hrtime(true);
            try {
                $check();
                $this->components->info(sprintf('%s: %.1f ms', $name, (hrtime(true) - $started) / 1_000_000));
            } catch (Throwable $exception) {
                $failed = true;
                $this->components->error("{$name}: {$exception->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
