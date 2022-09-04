<?php

return [
    'enabled'           => env('DBLOG_ENABLED', env('APP_ENV') === 'local'),
    'query_slower_than' => env('DBLOG_QUERY_SLOWER_THAN', 0),
    'trigger'           => env('DBLOG_TRIGGER'),
    'log_storage'       => env('DBLOG_LOG_STORAGE',env('FILESYSTEM_DRIVER')),
    'time_brackets'     => [2,4,5],
    'folder_path'       => 'dblogs',
];
