# Laravel DB Log (Performance & Query Logger)

![GitHub](https://img.shields.io/github/license/akshit-arora/dblog?logoColor=green)

A lightweight Laravel package to log slow database queries and long-running HTTP requests in your application. It helps you pinpoint performance bottlenecks by automatically tracking slow queries, identifying their exact source files, and catching N+1 issues by measuring overall page load times.

## Why use this package?
- Track down **slow SQL queries** dragging down your application speed.
- Catch **N+1 query issues** or slow PHP execution by monitoring overall page load times.
- Locate the **exact file and line number** responsible for the slow query.
- Maintain security with built-in **query sanitization** (hides sensitive bindings by default).
- Effortlessly organize logs bifurcated by date and execution time brackets.

## Requirements
- PHP `^8.1`
- Laravel `10.x`, `11.x`, `12.x`, or `13.x`

## Installation

Install the package via Composer:

```bash
composer require akshitarora/dblog
```

*(Note: If you are using Laravel 8 or 9, please use older releases of this package.)*

Laravel will automatically discover the service provider. However, if you've disabled auto-discovery, add the ServiceProvider in `config/app.php` or `bootstrap/providers.php` depending on your Laravel version:

```php
AkshitArora\DbLog\DbLogServiceProvider::class,
```

## Configuration

Publish the `dblog.php` configuration file to customize thresholds and features:

```bash
php artisan vendor:publish --provider="AkshitArora\DbLog\DbLogServiceProvider"
```

### Configuration Options Breakdown

| Option | Type | Default | Description |
|---|---|---|---|
| `enabled` | boolean | `true` (local) | Master switch to enable or disable the package logging entirely. |
| `query_slower_than` | float | `0` | Queries taking longer than this time (in seconds) will be logged. |
| `sanitize_queries` | boolean | `true` | When true, query bindings (e.g., passwords, emails) are replaced with `?` to prevent sensitive data leaks in logs. |
| `track_sources` | boolean | `true` | When true, attempts to trace the exact file and line number that executed the slow query. |
| `track_slow_pages` | boolean | `true` | Enables or disables the `TrackSlowPages` middleware from logging slow requests. |
| `page_slower_than` | float | `1.5` | Requests taking longer than this time (in seconds) will be logged (requires middleware). |
| `ignore_routes` | array | `[]` | Routes to completely ignore (supports `*` wildcards, e.g. `telescope/*`). |
| `trigger` | string | `null` | Force logs to record if a specific variable is found in the environment, HTTP Headers, GET, POST, or Cookies (great for debugging production secretly). |
| `folder_path` | string | `null` | Custom directory to store logs. If left `null`, logs go to `storage/logs/dblog/`. |
| `time_brackets` | array | `[2, 4, 5]` | Time thresholds used to group slow queries into different log files (e.g., separating 2-4s queries from 5s+ queries). |

## Features & Usage

### 1. Slow Query Logging
By default, the package will monitor your application's queries. You can configure `query_slower_than` in your `dblog.php` (or `.env` via `DBLOG_QUERY_SLOWER_THAN`) to specify the threshold in seconds. 

The logs are written to `storage/logs/dblog/` by default (or to `folder_path` if configured). Each slow query line includes its timestamp, database, SQL, duration, and request. Slow query files are named `slow_query_YYYYMMDD_<time-bracket>.log` (the bracket is omitted if `time_brackets` is empty):
```text
[2026-09-11 11:38:20] [database:database_name] SELECT * FROM users WHERE status = ? (1.25 s) [GET /users] [source: app/Http/Controllers/UserController.php:45]
```

### 2. Slow Page Tracking (Middleware)
While queries might be fast, rendering the page or executing PHP could be slow. To track overall slow requests, add the `TrackSlowPages` middleware to your global middleware stack in `bootstrap/app.php` (Laravel 11+) or `app/Http/Kernel.php` (Laravel 10):

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\AkshitArora\DbLog\Middleware\TrackSlowPages::class);
})
```

Configure `page_slower_than` in your config to set the threshold. It outputs to `slow_pages_YYYYMMDD.log` in the same folder:
```text
[2026-09-11 11:38:20] GET /heavy-dashboard (2.45 s)
```

### 3. Ignoring Specific Routes
Don't want to log queries from Telescope, Horizon, or your Admin panel? You can easily ignore them in the `config/dblog.php` file using wildcards:
```php
'ignore_routes' => [
    'telescope/*',
    'admin/reports/*',
],
```

## Testing

```bash
./vendor/bin/pest
```

## Security

Please ensure your `storage/logs` directory is not publicly accessible if you deploy this package to production.

## Credits
Special thanks to the original inspiration for query logging: [overtrue/laravel-query-logger](https://github.com/overtrue/laravel-query-logger).

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
