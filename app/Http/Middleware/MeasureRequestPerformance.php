<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MeasureRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $queryCount = 0;
        $queryTime = 0.0;

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryTime): void {
            $queryCount++;
            $queryTime += $query->time;
        });

        $response = $next($request);
        $duration = (hrtime(true) - $startedAt) / 1_000_000;

        if (! app()->isProduction()) {
            $response->headers->set(
                'Server-Timing',
                sprintf('app;dur=%.1f, db;dur=%.1f;desc="%d queries"', $duration, $queryTime, $queryCount)
            );
            $response->headers->set('X-Query-Count', (string) $queryCount);
        } elseif ($duration >= config('performance.slow_request_threshold_ms', 750)) {
            Log::warning('Slow application request', [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
                'response_type' => $response->headers->get('Content-Type'),
                'duration_ms' => round($duration, 1),
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTime, 1),
            ]);
        }

        return $response;
    }
}
