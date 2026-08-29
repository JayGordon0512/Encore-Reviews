<?php

use App\Http\Controllers\Admin\AudienceImportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ManualEventController;
use App\Http\Controllers\Admin\OrganisationController;
use App\Http\Controllers\Admin\ReviewModerationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredOrganiserController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ReviewSubmissionController;
use App\Http\Controllers\Web\ShowController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/organisers', [HomeController::class, 'organisers'])->name('organisers');
Route::get('/shows', [ShowController::class, 'index'])->name('shows.index');
Route::get('/shows/{show:slug}', [ShowController::class, 'show'])->name('shows.show');
Route::get('/review/submit', [ReviewSubmissionController::class, 'show'])->name('review.submit');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/organisers/create', [RegisteredOrganiserController::class, 'create'])->name('organisers.create');
    Route::post('/organisers', [RegisteredOrganiserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('organisers.store');
});

Route::middleware(['auth', 'admin.active'])->group(function (): void {
    Route::get('/admin', DashboardController::class)->name('admin.dashboard');
    Route::patch('/admin/reviews/{review}', [ReviewModerationController::class, 'update'])->name('admin.reviews.update');
    Route::get('/admin/events/create', [ManualEventController::class, 'create'])->name('admin.events.create');
    Route::post('/admin/events', [ManualEventController::class, 'store'])->name('admin.events.store');
    Route::get('/admin/events/{show}', [ManualEventController::class, 'show'])->name('admin.events.show');
    Route::patch('/admin/events/{show}/artwork', [ManualEventController::class, 'updateArtwork'])
        ->name('admin.events.artwork.update');
    Route::get('/admin/customer-import-template.csv', [AudienceImportController::class, 'template'])
        ->name('admin.audience-imports.template');
    Route::post('/admin/events/{show}/customers', [AudienceImportController::class, 'store'])
        ->name('admin.audience-imports.store');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin/encore')->name('super.')->middleware('super_admin')->group(function (): void {
        Route::get('/accounts', [OrganisationController::class, 'index'])->name('organisations.index');
        Route::get('/accounts/create', [OrganisationController::class, 'create'])->name('organisations.create');
        Route::post('/accounts', [OrganisationController::class, 'store'])->name('organisations.store');
        Route::get('/accounts/{organisation}/edit', [OrganisationController::class, 'edit'])->name('organisations.edit');
        Route::patch('/accounts/{organisation}', [OrganisationController::class, 'update'])->name('organisations.update');
        Route::get('/accounts/{organisation}/support', [OrganisationController::class, 'support'])->name('organisations.support');
        Route::post('/accounts/{organisation}/users', [OrganisationController::class, 'storeUser'])->name('organisations.users.store');
        Route::patch('/accounts/{organisation}/users/{user}', [OrganisationController::class, 'updateUser'])->name('organisations.users.update');
        Route::post('/accounts/{organisation}/shows', [OrganisationController::class, 'assignShow'])->name('organisations.shows.store');
        Route::delete('/accounts/{organisation}/shows/{show}', [OrganisationController::class, 'unassignShow'])->name('organisations.shows.destroy');
    });
});
