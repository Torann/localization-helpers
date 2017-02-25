<?php

namespace Torann\LocalizationHelpers;

use Illuminate\Support\ServiceProvider;

class LocalizationHelpersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/localization-helpers.php', 'localization-helpers'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole() === false) {
            return;
        }

        if ($this->isLumen() === false) {
            $this->publishes([
                __DIR__ . '/../config/localization-helpers.php' => config_path('localization-helpers.php'),
            ], 'config');
        }

        $this->commands([
            Commands\LocalizationMissing::class,
            Commands\LocalizationFind::class,
            Commands\ExportCommand::class,
            Commands\ImportCommand::class,
        ]);
    }

    /**
     * Check if package is running under Lumen app
     *
     * @return bool
     */
    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen') === true;
    }
}