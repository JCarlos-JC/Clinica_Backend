<?php

namespace App\Providers;

use App\Services\TriageServiceClient;
use App\Services\PatientServiceClient;
use App\Services\LaboratoryServiceClient;
use Illuminate\Support\ServiceProvider;

class MicroserviceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Triage Service Client
        $this->app->singleton(TriageServiceClient::class, function ($app) {
            return new TriageServiceClient();
        });

        // Register Patient Service Client
        $this->app->singleton(PatientServiceClient::class, function ($app) {
            return new PatientServiceClient();
        });

        // Register Laboratory Service Client
        $this->app->singleton(LaboratoryServiceClient::class, function ($app) {
            return new LaboratoryServiceClient();
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
