<?php

use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TicketPal\PerformanceUpsertController;
use App\Http\Controllers\Api\TicketPal\ReviewInvitationController;
use App\Http\Controllers\Api\TicketPal\ShowUpsertController;
use Illuminate\Support\Facades\Route;

Route::post('/reviews', [ReviewController::class, 'store']);

Route::prefix('ticketpal')->middleware(['ticketpal.secret', 'ticketpal.event'])->group(function (): void {
    Route::post('/shows/upsert', ShowUpsertController::class)->name('ticketpal.shows.upsert');
    Route::post('/performances/upsert', PerformanceUpsertController::class)->name('ticketpal.performances.upsert');
    Route::post('/invitations', [ReviewInvitationController::class, 'store'])->name('ticketpal.invitations.store');
});
