<?php

namespace App\Providers;

use App\Contracts\ProviderSecretResolver;
use App\Domain\ReviewEligibility\EligibilityIdGenerator;
use App\Infrastructure\Integration\ConfigurationProviderSecretResolver;
use App\Infrastructure\Persistence\UuidEligibilityIdGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProviderSecretResolver::class, ConfigurationProviderSecretResolver::class);
        $this->app->bind(EligibilityIdGenerator::class, UuidEligibilityIdGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
