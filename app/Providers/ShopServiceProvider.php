<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ShopService;
use App\Filters\ShopFilter;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ShopFilter::class, function ($app) {
            return new ShopFilter($app['request']);
        });

        $this->app->bind(ShopService::class, function ($app) {
            return new ShopService($app->make(ShopFilter::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
