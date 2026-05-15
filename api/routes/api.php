<?php

use App\Http\Controllers\Admin\AdminMapController;
use App\Http\Controllers\Admin\AdminSeasonController;
use App\Http\Controllers\Admin\AdminSeasonMapController;
use App\Http\Controllers\Auth\DiscordAuthController;
use App\Http\Controllers\Public\PublicMapController;
use App\Http\Controllers\Public\PublicSeasonController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [DiscordAuthController::class, 'me'])->middleware('auth:sanctum');

Route::get('/seasons', [PublicSeasonController::class, 'index']);
Route::get('/seasons/{slug}', [PublicSeasonController::class, 'show']);
Route::get('/maps/{uid}', [PublicMapController::class, 'show']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/maps', [AdminMapController::class, 'index']);
    Route::get('/maps/{map}', [AdminMapController::class, 'show']);
    Route::post('/maps/import', [AdminMapController::class, 'import']);
    Route::patch('/maps/{map}', [AdminMapController::class, 'update']);
    Route::delete('/maps/{map}', [AdminMapController::class, 'destroy']);

    Route::get('/seasons', [AdminSeasonController::class, 'index']);
    Route::get('/seasons/{season}', [AdminSeasonController::class, 'show']);
    Route::post('/seasons', [AdminSeasonController::class, 'store']);
    Route::patch('/seasons/{season}', [AdminSeasonController::class, 'update']);
    Route::delete('/seasons/{season}', [AdminSeasonController::class, 'destroy']);

    Route::post('/seasons/{season}/maps', [AdminSeasonMapController::class, 'store']);
    Route::patch('/seasons/{season}/maps/{map}', [AdminSeasonMapController::class, 'update']);
    Route::delete('/seasons/{season}/maps/{map}', [AdminSeasonMapController::class, 'destroy']);
});
