<?php

namespace AkshitArora\DbLog;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Storage;
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
            $timeInSeconds = $query->time / 1000;

            if ($timeInSeconds < $appConfig->get('dblog.query_slower_than', 0)) {
                return;
            }

            $sqlWithPlaceholders = str_replace(['%', '?', '%s%s'], ['%%', '%s', '?'], $query->sql);

            $bindings = $query->connection->prepareBindings($query->bindings);
            $pdo      = $query->connection->getPdo();
            $realSql  = $sqlWithPlaceholders;
            $duration = $this->formatDuration($timeInSeconds);

            if (count($bindings) > 0) {
                $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
            }

            $log = sprintf('[%s] [%s] %s || Path %s: %s', $query->connection->getDatabaseName(), $duration, $realSql,
                request()->method(), request()->getRequestUri());

            $disk = $appConfig->get('dblog.log_storage', 'local');

            // Set the file name
            $fileName = date('Ymd');

            if(is_array($appConfig->get('dblog.time_brackets'))) {
                $timeBrackets = $appConfig->get('dblog.time_brackets');

                sort($timeBrackets);

                $lessThanTime = 0;
                $moreThanTime = 0;

                foreach($timeBrackets as $timeBracket) {
                    $moreThanTime = $timeBracket;

                    if($timeInSeconds < $timeBracket) {
                        $fileName .= '_' . $lessThanTime . '_' . $moreThanTime . 's';
                        break;
                    }

                    $lessThanTime = $timeBracket;

                    if($timeBracket == end($timeBrackets)) {
                        $fileName .= '_' . $lessThanTime . 's_and_above';
                    }
                }
            }

            $logFile = $appConfig->get('dblog.folder_path').'/'. $fileName .'.log';

            Storage::disk($disk)->append($logFile, $log);
        });
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

    /**
     * Format duration.
     *
     * @param  float  $seconds
     *
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000).'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2).'ms';
        }

        return round($seconds, 2).'s';
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dblog.php', 'dblog');
    }
}
