<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold;

use CaminoDelDev\LaravelApiScaffold\Commands\ScaffoldApiCommand;
use CaminoDelDev\LaravelApiScaffold\Commands\ScaffoldInspectCommand;
use CaminoDelDev\LaravelApiScaffold\Commands\ScaffoldModelCommand;
use Illuminate\Support\ServiceProvider;

class ApiScaffoldServiceProvider extends ServiceProvider
{
    private const CONFIG_KEY = 'api-scaffold';

    private const CONFIG_TAG = 'api-scaffold-config';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-scaffold.php',
            self::CONFIG_KEY
        );
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/api-scaffold.php' => config_path('api-scaffold.php'),
        ], self::CONFIG_TAG);

        $this->commands([
            ScaffoldApiCommand::class,
            ScaffoldInspectCommand::class,
            ScaffoldModelCommand::class,
        ]);
    }
}
