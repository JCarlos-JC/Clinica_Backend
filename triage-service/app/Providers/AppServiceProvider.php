<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PatientServiceClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PatientServiceClient as singleton
        $this->app->singleton(PatientServiceClient::class, function ($app) {
            return new PatientServiceClient();
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
