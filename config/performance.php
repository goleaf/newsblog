<?php

return [
    'thresholds' => [
        // Slow query threshold in milliseconds
        'slow_query_ms' => (int) env('PERF_SLOW_QUERY_MS', 100),

        // Slow request threshold in milliseconds
        'slow_request_ms' => (int) env('PERF_SLOW_REQUEST_MS', 3000),

        // Memory alert threshold as percentage of memory_limit
        'memory_alert_percent' => (int) env('PERF_MEMORY_ALERT_PERCENT', 80),
    ],
];
