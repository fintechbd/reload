<?php

namespace Fintech\Reload;

use Fintech\Core\Traits\Packages\RegisterPackageTrait;
use Fintech\Reload\Commands\InstallCommand;
use Fintech\Reload\Commands\LeatherBackSetupCommand;
use Fintech\Reload\Providers\EventServiceProvider;
use Fintech\Reload\Providers\RepositoryServiceProvider;
use Illuminate\Support\ServiceProvider;

class ReloadServiceProvider extends ServiceProvider
{
    use RegisterPackageTrait;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->packageCode = 'reload';

        $this->mergeConfigFrom(
            __DIR__.'/../config/reload.php', 'fintech.reload'
        );

        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->injectOnConfig();

        $this->publishes([
            __DIR__.'/../config/reload.php' => config_path('fintech/reload.php'),
        ], 'fintech-reload-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'reload');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/reload'),
        ], 'fintech-reload-lang');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'reload');

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/reload'),
        ], 'fintech-reload-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                LeatherBackSetupCommand::class,
            ]);
        }
    }
}
