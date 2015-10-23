<?php namespace Torann\LocalizationHelpers;

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
        $this->publishes(array(
            __DIR__.'/../../config/config.php' => config_path('localization-helpers.php')
        ));
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bind('localization.missing', function( $app ) {
        	return new Commands\LocalizationMissing($app['config']->get('localization-helpers', []));
    	});

        $this->app->bind('localization.find', function( $app ) {
        	return new Commands\LocalizationFind($app['config']->get('localization-helpers', []));
    	});

        $this->app->bind('localization.export', function ($app) {
            return new Commands\ExportCommand($app['config']->get('localization-helpers', []));
        });

        $this->app->bind('localization.import', function ($app) {
            return new Commands\ImportCommand($app['config']->get('localization-helpers', []));
        });

    	$this->commands(
    		'localization.missing',
    		'localization.find',
            'localization.export',
            'localization.import'
    	);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}
}