<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ShowController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shows/{show:slug}', [ShowController::class, 'show'])->name('shows.show');