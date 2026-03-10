<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TriageServiceClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TriageServiceClient as singleton
        $this->app->singleton(TriageServiceClient::class, function ($app) {
            return new TriageServiceClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
