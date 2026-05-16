<?php

use App\Domains\Identity\Http\Controllers\DiscordAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
    ]);
})->name('home');

Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirect']);
Route::get('/auth/discord/callback', [DiscordAuthController::class, 'callback']);
Route::post('/auth/logout', [DiscordAuthController::class, 'logout'])->middleware('auth:sanctum');
