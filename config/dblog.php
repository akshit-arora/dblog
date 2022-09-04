<?php

return [
    'enabled' => env('DBLOG_ENABLED', env('APP_ENV') === 'local'),

    // Only record queries that are slower than the following time
    // Unit: seconds
    'query_slower_than' => env('DBLOG_QUERY_SLOWER_THAN', 0),

    // Only record queries when the DBLOG_TRIGGER is set in the environment,
    // or when the trigger HEADER, GET, POST, or COOKIE variable is set.
    'trigger' => env('DBLOG_TRIGGER'),

    // Log storage location
    'log_storage' => env('DBLOG_LOG_STORAGE',env('FILESYSTEM_DRIVER')),

    // Time brackets in which the queries are to be bifurcated
    'time_brackets' => [2,4,5],

    // Set the path in the storage folder where logs need to be stored
    'folder_path' => 'dblogs',
];
