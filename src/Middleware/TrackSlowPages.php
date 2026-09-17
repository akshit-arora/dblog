<?php

namespace AkshitArora\DbLog\Middleware;

use AkshitArora\DbLog\LogWriter;
use Closure;
use Illuminate\Http\Request;

class TrackSlowPages
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Terminate the request and log if it was slow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        $appConfig = app('config');

        if (!$appConfig->get('dblog.enabled', false) || !$appConfig->get('dblog.track_slow_pages', true)) {
            return;
        }

        if ($this->shouldIgnoreRoute($request, $appConfig->get('dblog.ignore_routes', []))) {
            return;
        }

        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT');
        $duration = microtime(true) - $startTime;

        if ($duration >= $appConfig->get('dblog.page_slower_than', 1.5)) {
            $folderPath = $appConfig->get('dblog.folder_path') ?? storage_path('logs/dblog');
            $fileName = 'slow_pages_' . date('Ymd') . '.log';

            $log = sprintf('%s %s (%.2f s)', $request->method(), $request->getRequestUri(), $duration);
            LogWriter::append($folderPath, $fileName, $log);
        }
    }

    /**
     * Determine if the request has a URI that should be ignored.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $ignoredRoutes
     * @return bool
     */
    protected function shouldIgnoreRoute($request, array $ignoredRoutes)
    {
        foreach ($ignoredRoutes as $route) {
            if ($route !== '/') {
                $route = trim($route, '/');
            }
            
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }
}
