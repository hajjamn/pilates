<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Le rotte sono tutte incapsulate nel prefisso "gdp-template", così da
| poter essere raggiunte da URL come:
| https://generazionedigitaleprogrammi.com/gdp-template/
|
*/

Route::prefix('gdp-template')->group(function () {

    Route::get('/', function () {
        return view('welcome');
    });

    Route::middleware(['auth', 'verified'])
        ->name('admin.')
        ->prefix('admin')
        ->group(function () {

            Route::get('/', function () {
                return view('admin.dashboard');
            })->name('dashboard');

            // Register all other protected routes
            // CRUD EXAMPLES, etc.
        });

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__ . '/auth.php';
});
