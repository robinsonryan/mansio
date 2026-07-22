<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RobinsonRyan\Mansio\Contracts\ContentStore;
use RobinsonRyan\Mansio\Contracts\TokenGenerator;
use RobinsonRyan\Mansio\Storage\FlysystemContentStore;
use RobinsonRyan\Mansio\Support\Base62TokenGenerator;

final class MansioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mansio.php', 'mansio');

        $this->app->singleton(TokenGenerator::class, fn (): TokenGenerator => new Base62TokenGenerator(
            (int) config('mansio.token.length', 32),
        ));

        $this->app->singleton(ContentStore::class, function ($app): ContentStore {
            $factory = $app->make(FilesystemFactory::class);

            return new FlysystemContentStore(
                $factory->disk(config('mansio.store.disk', 'local')),
                (string) config('mansio.store.path', 'mansio'),
            );
        });

        $this->app->singleton(Mansio::class);
        $this->app->alias(Mansio::class, 'mansio');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mansio');
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mansio.php' => config_path('mansio.php'),
            ], 'mansio-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'mansio-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/mansio'),
            ], 'mansio-views');
        }
    }

    private function registerRoutes(): void
    {
        $config = (array) config('mansio.route', []);

        Route::group([
            'prefix' => $config['prefix'] ?? 'docs',
            'middleware' => $config['middleware'] ?? ['web'],
            'domain' => $config['domain'] ?? null,
        ], function (): void {
            $this->loadRoutesFrom(__DIR__ . '/../routes/mansio.php');
        });
    }
}
