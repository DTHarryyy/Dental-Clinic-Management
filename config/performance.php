<?php

return [
    'slow_request_threshold_ms' => (int) env('SLOW_REQUEST_THRESHOLD_MS', 750),
    'turbo_enabled' => (bool) env('TURBO_ENABLED', true),
];
