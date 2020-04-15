<?php

namespace App\Providers;

use App\Services\Implementations\InbentaApiService;
use Illuminate\Support\ServiceProvider;

class InbentaApiServiceProvider extends ServiceProvider
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
        $this->app->bind('App\Services\Interfaces\InbentaApiInterface', function ($app) {
            return new InbentaApiService();
        });
    }
}
