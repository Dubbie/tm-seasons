<?php

use App\Http\Controllers\Admin\AdminClubController;
use App\Http\Controllers\Admin\AdminClubMemberController;
use App\Http\Controllers\Admin\AdminLeaderboardPollController;
use App\Http\Controllers\Admin\AdminMapController;
use App\Http\Controllers\Admin\AdminSeasonController;
use App\Http\Controllers\Admin\AdminSeasonMapController;
use App\Http\Controllers\Admin\AdminSeasonPollController;
use App\Http\Controllers\Admin\AdminSeasonScoringController;
use App\Http\Controllers\Auth\DiscordAuthController;
use App\Http\Controllers\Public\PublicClubController;
use App\Http\Controllers\Public\PublicMapController;
use App\Http\Controllers\Public\PublicSeasonController;
use App\Http\Controllers\Public\PublicSeasonLeaderboardController;
use App\Http\Controllers\Public\PublicSeasonScoringController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [DiscordAuthController::class, 'me'])->middleware('auth:sanctum')->name('me');

Route::name('seasons.')->group(function (): void {
    Route::get('/seasons', [PublicSeasonController::class, 'index'])->name('index');
    Route::get('/seasons/{slug}', [PublicSeasonController::class, 'show'])->name('show');
    Route::get('/seasons/{slug}/leaderboard', [PublicSeasonLeaderboardController::class, 'seasonLeaderboard'])->name('leaderboard');
    Route::get('/seasons/{slug}/maps/{map}/leaderboard', [PublicSeasonLeaderboardController::class, 'mapLeaderboard'])->name('maps.leaderboard');

    Route::get('/seasons/{slug}/standings', [PublicSeasonScoringController::class, 'standings'])->name('standings');
    Route::get('/seasons/{slug}/events', [PublicSeasonScoringController::class, 'events'])->name('events');
    Route::get('/seasons/{slug}/players/{player}', [PublicSeasonScoringController::class, 'player'])->name('player');
});

Route::get('/maps/{uid}', [PublicMapController::class, 'show'])->name('maps.show');

Route::name('clubs.')->group(function (): void {
    Route::get('/clubs', [PublicClubController::class, 'index'])->name('index');
    Route::get('/clubs/{club}', [PublicClubController::class, 'show'])->name('show');
    Route::get('/clubs/{club}/members', [PublicClubController::class, 'members'])->name('members');
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::name('maps.')->group(function (): void {
        Route::get('/maps', [AdminMapController::class, 'index'])->name('index');
        Route::get('/maps/{map}', [AdminMapController::class, 'show'])->name('show');
        Route::post('/maps/import', [AdminMapController::class, 'import'])->name('import');
        Route::patch('/maps/{map}', [AdminMapController::class, 'update'])->name('update');
        Route::delete('/maps/{map}', [AdminMapController::class, 'destroy'])->name('destroy');
    });

    Route::name('seasons.')->group(function (): void {
        Route::get('/seasons', [AdminSeasonController::class, 'index'])->name('index');
        Route::get('/seasons/{season}', [AdminSeasonController::class, 'show'])->name('show');
        Route::post('/seasons', [AdminSeasonController::class, 'store'])->name('store');
        Route::patch('/seasons/{season}', [AdminSeasonController::class, 'update'])->name('update');
        Route::delete('/seasons/{season}', [AdminSeasonController::class, 'destroy'])->name('destroy');
    });

    Route::name('seasons.maps.')->group(function (): void {
        Route::post('/seasons/{season}/maps', [AdminSeasonMapController::class, 'store'])->name('store');
        Route::patch('/seasons/{season}/maps/{map}', [AdminSeasonMapController::class, 'update'])->name('update');
        Route::delete('/seasons/{season}/maps/{map}', [AdminSeasonMapController::class, 'destroy'])->name('destroy');
    });

    Route::name('seasons.')->group(function (): void {
        Route::post('/seasons/{season}/poll', [AdminSeasonPollController::class, 'poll'])->name('poll');
        Route::get('/seasons/{season}/records', [AdminSeasonPollController::class, 'records'])->name('records');

        Route::get('/seasons/{season}/points', [AdminSeasonScoringController::class, 'standings'])->name('points');
        Route::get('/seasons/{season}/events', [AdminSeasonScoringController::class, 'events'])->name('events');
        Route::post('/seasons/{season}/recalculate', [AdminSeasonScoringController::class, 'recalculate'])->name('recalculate')->middleware('throttle:3,10');
    });

    Route::name('clubs.')->group(function (): void {
        Route::get('/clubs', [AdminClubController::class, 'index'])->name('index');
        Route::get('/clubs/{club}', [AdminClubController::class, 'show'])->name('show');
        Route::post('/clubs/sync', [AdminClubController::class, 'sync'])->name('sync');
        Route::get('/clubs/{club}/members', [AdminClubMemberController::class, 'index'])->name('members.index');
    });

    Route::name('club.')->group(function (): void {
        Route::get('/club', [AdminClubController::class, 'primary'])->name('primary');
        Route::post('/club/sync', [AdminClubController::class, 'syncPrimary'])->name('syncPrimary');
        Route::get('/club/members', [AdminClubMemberController::class, 'primary'])->name('members');
    });

    Route::name('polls.')->group(function (): void {
        Route::get('/polls', [AdminLeaderboardPollController::class, 'index'])->name('index');
        Route::get('/polls/{poll}', [AdminLeaderboardPollController::class, 'show'])->name('show');
    });
});
