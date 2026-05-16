<?php

use App\Domains\Trackmania\Http\Controllers\Admin\AdminClubController;
use App\Domains\Trackmania\Http\Controllers\Admin\AdminClubMemberController;
use App\Domains\Trackmania\Http\Controllers\Admin\AdminMapController;
use App\Domains\Trackmania\Http\Controllers\Public\PublicClubController;
use App\Domains\Trackmania\Http\Controllers\Public\PublicMapController;
use Illuminate\Support\Facades\Route;

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
});
