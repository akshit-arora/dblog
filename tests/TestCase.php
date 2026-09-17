<?php

namespace AkshitArora\DbLog\Tests;

use Illuminate\Foundation\Application;
use AkshitArora\DbLog\DbLogServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database();
    }


    protected function database(): void
    {
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    $users = Collection::times(100 , fn($number) => ['name' => "User #{$number}"])->toArray();
    
    DB::table('users')->insert($users);
    }
    
    /**
     * Define environment setup.
     *
     * @param Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('app.key', 'base64:f9st7J/mtBN80JbMjXFbTLXitClvVSjCN3/y31yxYO0=');

        $app['config']->set('database.connections.testbench', [
            'driver'   => env('DB_DRIVER', 'sqlite'),
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '3306'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'database' => env('DB_DATABASE', ':memory:'),
            'prefix'   => '',
        ]);

        $app['config']->set('dblog.enabled', true);
        $app['config']->set('dblog.folder_path', storage_path('app/dblogs'));
        $app['config']->set('dblog.query_slower_than', 0);
    }

    protected function getPackageProviders($app)
    {
        return [
            DbLogServiceProvider::class,

        ];
    }
}
