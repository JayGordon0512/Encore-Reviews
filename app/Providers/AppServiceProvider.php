<?php

namespace App\Providers;

use App\Contracts\ProviderSecretResolver;
use App\Contracts\ReviewInvitationSender;
use App\Domain\ReviewEligibility\EligibilityIdGenerator;
use App\Infrastructure\Integration\ConfigurationProviderSecretResolver;
use App\Infrastructure\Notifications\LaravelReviewInvitationSender;
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
        $this->app->bind(ReviewInvitationSender::class, LaravelReviewInvitationSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
