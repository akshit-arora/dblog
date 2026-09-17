<?php

namespace AkshitArora\DbLog;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;

class DbLogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/dblog.php' => config_path('dblog.php'),
        ]);

        $this->logQueries();
    }

    public function logQueries()
    {
        $appConfig = $this->app['config'];

        if (!$appConfig->get('dblog.enabled', false)) {
            return;
        }

        $trigger = $appConfig->get('dblog.trigger');

        if (!empty($trigger) && !$this->requestHasTrigger($trigger)) {
            return;
        }

        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) use ($appConfig) {
            // Check if current route is ignored
            if ($this->shouldIgnoreRoute($appConfig->get('dblog.ignore_routes', []))) {
                return;
            }

            $timeInSeconds = $query->time / 1000;

            if ($timeInSeconds < $appConfig->get('dblog.query_slower_than', 0)) {
                return;
            }

            // Sanitize or Interpolate Bindings
            if ($appConfig->get('dblog.sanitize_queries', true)) {
                $realSql = $query->sql;
            } else {
                $sqlWithPlaceholders = str_replace(['%', '?', '%s%s'], ['%%', '%s', '?'], $query->sql);
                $bindings = $query->connection->prepareBindings($query->bindings);
                $pdo      = $query->connection->getPdo();
                $realSql  = $sqlWithPlaceholders;

                if (count($bindings) > 0 && $pdo) {
                    $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
                }
            }

            // Get Request Context
            $method = app()->runningInConsole() ? 'CLI' : request()->method();
            $path = app()->runningInConsole() ? 'Command' : request()->getRequestUri();
            
            // Get Source (File/Line)
            $source = null;
            if ($appConfig->get('dblog.track_sources', true)) {
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                foreach ($backtrace as $trace) {
                    if (isset($trace['file']) && !str_contains($trace['file'], 'vendor/') && !str_contains($trace['file'], 'DbLogServiceProvider')) {
                        $source = sprintf('%s:%s', $trace['file'], $trace['line']);
                        break;
                    }
                }
            }

            $log = sprintf(
                '[database:%s] %s (%.2f s) [%s %s]',
                $query->connection->getDatabaseName(),
                $realSql,
                $timeInSeconds,
                $method,
                $path
            );

            if ($source !== null) {
                $log .= sprintf(' [source: %s]', $source);
            }

            $fileName = 'slow_query_' . date('Ymd') . $this->timeBracketSuffix(
                $timeInSeconds,
                $appConfig->get('dblog.time_brackets')
            ) . '.log';

            $folderPath = $appConfig->get('dblog.folder_path') ?? storage_path('logs/dblog');
            LogWriter::append($folderPath, $fileName, $log);
        });
    }

    /**
     * Return the filename suffix for the duration bracket, when configured.
     */
    private function timeBracketSuffix(float $duration, mixed $timeBrackets): string
    {
        if (!is_array($timeBrackets) || $timeBrackets === []) {
            return '';
        }

        sort($timeBrackets);
        $lowerBound = 0;

        foreach ($timeBrackets as $upperBound) {
            if ($duration < $upperBound) {
                return '_' . $lowerBound . '_' . $upperBound . 's';
            }

            $lowerBound = $upperBound;
        }

        return '_' . $lowerBound . 's_and_above';
    }

    /**
     * Determine if the request has a URI that should be ignored.
     *
     * @param  string  $requestPath
     * @param  array  $ignoredRoutes
     * @return bool
     */
    protected function shouldIgnoreRoute(array $ignoredRoutes)
    {
        if (app()->runningInConsole()) {
            return false;
        }

        foreach ($ignoredRoutes as $route) {
            if ($route !== '/') {
                $route = trim($route, '/');
            }
            
            if (request()->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $trigger
     *
     * @return bool
     */
    public function requestHasTrigger($trigger)
    {
        return false !== getenv($trigger) || \request()->hasHeader($trigger) || \request()->has($trigger) || \request()->hasCookie($trigger);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dblog.php', 'dblog');
    }
}
