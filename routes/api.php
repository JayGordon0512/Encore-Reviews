<?php

use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TicketPal\PerformanceUpsertController;
use App\Http\Controllers\Api\TicketPal\ReviewInvitationController;
use App\Http\Controllers\Api\TicketPal\ShowUpsertController;
use App\Http\Controllers\Api\V2\CatalogueImportController;
use App\Http\Controllers\Api\V2\ReviewEligibilityController;
use Illuminate\Support\Facades\Route;

Route::post('/reviews', [ReviewController::class, 'store']);

Route::prefix('v2/integrations')->middleware(['provider.v2.enabled', 'provider.v2.auth'])->group(function (): void {
    Route::post('/catalogue/organisations', [CatalogueImportController::class, 'organisation'])
        ->defaults('provider_operation', 'catalogue-organisation:write')
        ->name('provider.v2.catalogue.organisations.upsert');
    Route::post('/catalogue/organisation-user-memberships', [CatalogueImportController::class, 'membership'])
        ->defaults('provider_operation', 'catalogue-membership:write')
        ->name('provider.v2.catalogue.memberships.upsert');
    Route::post('/catalogue/shows', [CatalogueImportController::class, 'show'])
        ->defaults('provider_operation', 'catalogue-show:write')
        ->name('provider.v2.catalogue.shows.upsert');
    Route::post('/catalogue/performances', [CatalogueImportController::class, 'performance'])
        ->defaults('provider_operation', 'catalogue-performance:write')
        ->name('provider.v2.catalogue.performances.upsert');
    Route::post('/review-invitation-eligibilities', [ReviewEligibilityController::class, 'eligibility'])
        ->defaults('provider_operation', 'review-eligibility:write')
        ->name('provider.v2.review-eligibilities.accept');
    Route::post('/review-invitation-withdrawals', [ReviewEligibilityController::class, 'withdrawal'])
        ->defaults('provider_operation', 'review-withdrawal:write')
        ->name('provider.v2.review-eligibilities.withdraw');
});

Route::prefix('ticketpal')->middleware(['ticketpal.secret', 'ticketpal.event'])->group(function (): void {
    Route::post('/shows/upsert', ShowUpsertController::class)->name('ticketpal.shows.upsert');
    Route::post('/performances/upsert', PerformanceUpsertController::class)->name('ticketpal.performances.upsert');
    Route::post('/invitations', [ReviewInvitationController::class, 'store'])->name('ticketpal.invitations.store');
});
