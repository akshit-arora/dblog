<?php

return [
    'enabled' => env('DBLOG_ENABLED', env('APP_ENV') === 'local'),

    // Only record queries that are slower than the following time
    // Unit: seconds
    'query_slower_than' => env('DBLOG_QUERY_SLOWER_THAN', 0),

    // Sanitize queries (hide bindings to prevent logging sensitive details)
    'sanitize_queries' => env('DBLOG_SANITIZE_QUERIES', true),

    // Track query source (file and line number)
    'track_sources' => env('DBLOG_TRACK_SOURCES', true),

    // Slow page tracking
    'track_slow_pages' => env('DBLOG_TRACK_SLOW_PAGES', true),
    'page_slower_than' => env('DBLOG_PAGE_SLOWER_THAN', 1.5),

    // Routes to ignore for both slow query and slow page logging
    // Can use wildcard '*' e.g., 'admin/*'
    'ignore_routes' => [
        // 'telescope/*',
    ],

    // Only record queries when the DBLOG_TRIGGER is set in the environment,
    // or when the trigger HEADER, GET, POST, or COOKIE variable is set.
    'trigger' => env('DBLOG_TRIGGER'),

    // Set the path in the default logs folder where logs need to be stored
    // If null, it defaults to storage_path('logs/dblog')
    'folder_path' => env('DBLOG_FOLDER_PATH', null),

    // Time brackets in which the queries are to be bifurcated
    'time_brackets' => [2,4,5],
];
