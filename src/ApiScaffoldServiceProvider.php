<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold;

use CaminoDelDev\LaravelApiScaffold\Commands\ScaffoldApiCommand;
use CaminoDelDev\LaravelApiScaffold\Commands\ScaffoldInspectCommand;
use CaminoDelDev\LaravelApiScaffold\Commands\ScaffoldModelCommand;
use Illuminate\Support\ServiceProvider;

class ApiScaffoldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-scaffold.php',
            'api-scaffold'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/api-scaffold.php' => config_path('api-scaffold.php'),
        ], 'api-scaffold-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ScaffoldApiCommand::class,
                ScaffoldInspectCommand::class,
                ScaffoldModelCommand::class,
            ]);
        }
    }
}
