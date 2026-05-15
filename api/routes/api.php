<?php

use App\Http\Controllers\Auth\DiscordAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [DiscordAuthController::class, 'me'])->middleware('auth:sanctum');
