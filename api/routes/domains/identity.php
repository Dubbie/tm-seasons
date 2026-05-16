<?php

use App\Domains\Identity\Http\Controllers\DiscordAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [DiscordAuthController::class, 'me'])
    ->middleware('auth:sanctum')
    ->name('me');
