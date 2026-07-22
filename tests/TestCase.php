<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use RobinsonRyan\Mansio\MansioServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            MansioServiceProvider::class,
        ];
    }

    /**
     * Package migrations are auto-loaded by the service provider; this adds the
     * UUID-keyed fixture table used to stand in for a consumer's shareable model.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Mansio's UUID7 model relies on Postgres 18's native `uuidv7()` column default,
     * so the suite runs against Postgres rather than SQLite. Defaults target the
     * DDEV `db` service; override via env when running elsewhere.
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'db'),
            'port' => (int) env('DB_PORT', 5432),
            'database' => env('MANSIO_TEST_DB', 'mansio_testing'),
            'username' => env('DB_USERNAME', 'db'),
            'password' => env('DB_PASSWORD', 'db'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);

        $app['config']->set('filesystems.disks.mansio_test', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/mansio'),
            'throw' => true,
        ]);

        $app['config']->set('mansio.store.disk', 'mansio_test');
    }
}
