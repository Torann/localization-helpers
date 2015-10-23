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
		$this->app['localization.missing'] = $this->app->share( function( $app ) {
        	return new Commands\LocalizationMissing($app['config']['localization-helpers']);
    	});

		$this->app['localization.find'] = $this->app->share( function( $app ) {
        	return new Commands\LocalizationFind($app['config']['localization-helpers']);
    	});

    	$this->commands(
    		'localization.missing',
    		'localization.find'
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