<?php

use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TicketPal\ShowUpsertController;
use Illuminate\Support\Facades\Route;

Route::post('/reviews', [ReviewController::class, 'store']);

Route::prefix('ticketpal')->middleware(['ticketpal.secret'])->group(function (): void {
    Route::post('/shows/upsert', ShowUpsertController::class);
});
