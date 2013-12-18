<?php namespace Scalia\SphinxSearch;

use Illuminate\Support\ServiceProvider;
use Sphinx\SphinxClient;

class SphinxSearchServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('scalia/sphinxsearch');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['sphinxconnection'] = $this->app->share(function($app)
        {
            $connection = SphinxClient();
            $connection->setServer($app['config']->get('sphinxsearch::host'),
                $app['config']->get('sphinxsearch::port'));
            $connection->setMatchMode(SphinxClient::SPH_MATCH_ANY);
            $connection->setSortMode(SphinxClient::SPH_SORT_RELEVANCE);
            return $connection;
        });

		$this->app['sphinxsearch'] = $this->app->share(function($app)
		{
			return new SphinxSearch($app->make('sphinxconnection'));
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('sphinxsearch');
	}

}
