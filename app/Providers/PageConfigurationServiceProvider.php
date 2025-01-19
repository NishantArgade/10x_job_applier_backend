<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PageConfigurationService;

class PageConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the RedirectService to the service container
        $this->app->singleton(PageConfigurationService::class, function ($app) {
            return new PageConfigurationService();
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
