<?php

namespace Fintech\Reload;

use Fintech\Reload\Commands\InstallCommand;
use Fintech\Reload\Commands\ReloadCommand;
use Illuminate\Support\ServiceProvider;

class ReloadServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/reload.php', 'fintech.reload'
        );

        $this->app->register(RouteServiceProvider::class);
        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/reload.php' => config_path('fintech/reload.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'reload');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/reload'),
        ]);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'reload');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/reload'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ReloadCommand::class,
            ]);
        }
    }
}
