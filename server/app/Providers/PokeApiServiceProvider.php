<?php

namespace App\Providers;

use App\Services\Implementations\PokeApiService;
use Illuminate\Support\ServiceProvider;

class PokeApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind('App\Services\Interfaces\PokeApiInterface', function ($app) {
            return new PokeApiService();
        });
    }
}
