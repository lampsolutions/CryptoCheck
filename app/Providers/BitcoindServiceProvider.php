<?php

namespace App\Providers;

use Denpa\Bitcoin\ClientFactory;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class BitcoindServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(config_path('bitcoind.php'), 'bitcoind');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAliases();

        $this->registerFactory();
        $this->registerClient();
    }

    /**
     * Register aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $aliases = [
            'bitcoind'         => 'Denpa\Bitcoin\ClientFactory',
            'bitcoind.client'  => 'Denpa\Bitcoin\LaravelClient',
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }

    /**
     * Register client factory.
     *
     * @return void
     */
    protected function registerFactory()
    {
        $this->app->singleton('bitcoind', function ($app) {
            return new ClientFactory(config('bitcoind'), $app['log']);
        });
    }

    /**
     * Register client shortcut.
     *
     * @return void
     */
    protected function registerClient()
    {
        $this->app->bind('bitcoind.client', function ($app) {
            return $app['bitcoind']->client();
        });
    }
}
