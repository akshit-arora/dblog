<?php

use Illuminate\Support\Facades\{DB,Schema,Storage,File};
use Illuminate\Support\Collection;
use AkshitArora\DbLog\Middleware\TrackSlowPages;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->logsPath = storage_path('app/dblogs');

    // Override config for testing to keep it in app/dblogs and disable sanitization to test bindings
    config([
        'dblog.folder_path' => $this->logsPath,
        'dblog.sanitize_queries' => false,
    ]);

    if (File::isDirectory($this->logsPath)) {
        File::deleteDirectory($this->logsPath);
    }
});

it('creates users', function () {
    expect(DB::table('users'))
       ->get()
       ->toHaveCount(100);
});

it('does not log a short query', function () {
    config(['dblog.query_slower_than' => 10]);

    expect(DB::table('users'))->get();    
    
    expect(File::isDirectory($this->logsPath))->toBeFalse();
});

it('logs a long query', function () {
    $users = Collection::times(100000 , fn($number) => ['name' => "User #{$number}"])->toArray();
    
    DB::table('users')->insert($users);

    $files = File::allFiles($this->logsPath);
    
    expect($files)->toHaveCount(1);

    $content = trim(File::get($files[0]));
    expect(basename($files[0]->getPathname()))
        ->toMatch('/^slow_query_\d{8}_.*\.log$/')
        ->and($content)->toMatch('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \[database:[^\]]+\] insert into "users" \("name"\)/')
        ->and($content)->toContain('(\'User #600\')')
        ->and($content)->toMatch('/\(\d+\.\d{2} s\) \[CLI Command\]/');
});

it('logs sanitized queries when enabled', function () {
    config(['dblog.sanitize_queries' => true]);

    $users = Collection::times(100000 , fn($number) => ['name' => "User #{$number}"])->toArray();
    DB::table('users')->insert($users);

    $files = File::allFiles($this->logsPath);
    expect($files)->toHaveCount(1);
    
    $content = File::get($files[0]);
    expect($content)
        ->toContain('insert into "users" ("name")')
        ->not->toContain('\'User #600\')')
        ->toContain('?');
});

it('does not apply route ignores to console queries', function () {
    config(['dblog.ignore_routes' => ['admin/*']]);
    app()->instance('request', Request::create('/admin/reports', 'GET'));

    DB::table('users')->count();

    expect(File::allFiles($this->logsPath))->toHaveCount(1)
        ->and(File::get(File::allFiles($this->logsPath)[0]))
        ->toContain('[CLI Command]');
});

it('logs slow pages above the configured threshold', function () {
    config(['dblog.page_slower_than' => 1]);
    $request = Request::create('/slow-page', 'GET', server: [
        'REQUEST_TIME_FLOAT' => microtime(true) - 2,
    ]);

    (new TrackSlowPages())->terminate($request, null);

    $files = File::allFiles($this->logsPath);
    $content = trim(File::get($files[0]));
    expect($files)->toHaveCount(1)
        ->and(basename($files[0]->getPathname()))
        ->toMatch('/^slow_pages_\d{8}\.log$/')
        ->and($content)->toMatch('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] GET \/slow-page \(\d+\.\d{2} s\)$/');
});

it('does not log pages below the configured threshold', function () {
    config(['dblog.page_slower_than' => 10]);
    $request = Request::create('/fast-page', 'GET', server: [
        'REQUEST_TIME_FLOAT' => microtime(true),
    ]);

    (new TrackSlowPages())->terminate($request, null);

    expect(File::isDirectory($this->logsPath))->toBeFalse();
});

it('respects slow page tracking and ignored route settings', function () {
    $request = Request::create('/admin/dashboard', 'GET', server: [
        'REQUEST_TIME_FLOAT' => microtime(true) - 2,
    ]);
    config(['dblog.page_slower_than' => 1, 'dblog.ignore_routes' => ['admin/*']]);

    (new TrackSlowPages())->terminate($request, null);
    config(['dblog.ignore_routes' => [], 'dblog.track_slow_pages' => false]);
    (new TrackSlowPages())->terminate($request, null);

    expect(File::isDirectory($this->logsPath))->toBeFalse();
});
