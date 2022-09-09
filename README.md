# Laravel DB Log Library

![GitHub](https://img.shields.io/github/license/akshit-arora/dblog?logoColor=green)

A simple Laravel library for devs to log your heavy queries bifurcated by the time taken in your application.

## Useful when
- You want to track the queries which are making your application slow.
- You want to monitor the application for the queries which is taking way longer than expected.
- You want to find the slow pages in your application

## Features
- Bifurcates the logs datewise so that you can know on what day was your application slow.
- You can also bifurcate the logs based on time taken by the query.

## Install

    composer require akshitarora/dblog

Add the ServiceProvider in `app.php`

    AkshitArora\DbLog\DbLogServiceProvider::class,

## Log structure

`[database-name] [time-taken s] SELECT SQL QUERY WHERE PARAMETERS='VALUE' || Path METHOD: /slow/page/here`

## Configuration

Publish `dblog.php` configuration file into `/config/` for configuration customization:

    php artisan vendor:publish --provider=AkshitArora\DbLog\DbLogServiceProvider

### Configuration options

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

## WARNING

**The library writes the slow SQL queries log in the storage folder. Please ensure that the storage folder is not publically accessible if you've kept this library in production.**

Special thanks to the package: https://github.com/overtrue/laravel-query-logger
