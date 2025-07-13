<?php

use App\Http\Controllers\Admin\UserController; //as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('gdp-template')->group(function () {

    //------------------------------
    // GUEST AREA
    //------------------------------
    Route::get('/', function () {
        return view('welcome');
    });

    //------------------------------
    // SHARED PROFILE
    //------------------------------
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    //------------------------------
    // ADMIN AREA
    //------------------------------
    Route::middleware(['auth', 'verified'])
        ->prefix('amministrazione')
        ->name('admin.')
        ->group(function () {

            Route::get('/', function () {
                return view('admin.dashboard');
            })->name('dashboard');

            Route::resource('/utenti', UserController::class)
                ->names('users')
                ->parameters(['utenti' => 'user']);
        });

    //------------------------------
    // OPERATOR AREA
    //------------------------------
    Route::middleware(['auth', 'verified', 'role:operatore|admin'])
        ->prefix('operatore')
        ->name('operator.')
        ->group(function () {
            Route::get('/', function () {
                return view('operator.dashboard');
            })->name('dashboard');
        });

    //------------------------------
    // CLIENT AREA
    //------------------------------
    Route::middleware(['auth', 'verified', 'role:cliente'])
        ->prefix('cliente')
        ->name('client.')
        ->group(function () {
            Route::get('/', function () {
                return view('client.dashboard');
            });
        });

    require __DIR__ . '/auth.php';
});
