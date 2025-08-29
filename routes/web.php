<?php

use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\LessonBookingController;
use App\Http\Controllers\LessonCalendarController;
use App\Http\Controllers\Operator\AvailabilityController as OperatorAvailabilityController;
use App\Http\Controllers\Admin\AvailabilityController as AdminAvailabilityController;
use App\Http\Controllers\Operator\OperatorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('gdp-template')->group(function () {

    //------------------------------
    // HOME
    //------------------------------
    Route::get('/', HomeRedirectController::class)->name('home.redirect');

    //------------------------------
    // SHARED PROFILE
    //------------------------------
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    //------------------------------
    // CALENDAR
    //------------------------------
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/calendario-lezioni', [LessonCalendarController::class, 'index'])
            ->name('calendar.lessons.index');
    });


    //------------------------------
    // LESSON BOOKINGS
    //------------------------------
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/lessons/{lesson}/book', [LessonBookingController::class, 'store'])
            ->name('lessons.book');

        Route::delete('/bookings/{booking}', [LessonBookingController::class, 'destroy'])
            ->name('bookings.cancel');
    });

    //------------------------------
    // ADMIN AREA
    //------------------------------
    Route::middleware(['auth', 'verified', 'role:admin'])
        ->prefix('amministrazione')
        ->name('admin.')
        ->group(function () {

            Route::get('/', function () {
                return view('admin.dashboard');
            })->name('dashboard');

            /* Route::resource('/utenti', UserController::class)
                ->names('users')
                ->parameters(['utenti' => 'user']); */

            Route::get('/disponibilita-settimanale', [AdminAvailabilityController::class, 'index'])
                ->name('availability.index');

            // Form/Anteprima generazione lezioni per intervallo [da, a]
            Route::get('/disponibilita-settimanale/genera', [AdminAvailabilityController::class, 'showGenerate'])
                ->name('availability.generate.form');

            // Esecuzione generazione (idempotente) nel range scelto
            Route::post('/disponibilita-settimanale/genera', [AdminAvailabilityController::class, 'generate'])
                ->name('availability.generate.run');
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

            Route::resource('/operatori', OperatorController::class)
                ->names('operators')
                ->parameters(['operatori' => 'operator']);

            Route::get('/disponibilita-settimanale', [OperatorAvailabilityController::class, 'show'])
                ->name('availability.show');
        });

    //------------------------------
    // CLIENT AREA
    //------------------------------
    Route::middleware(['auth', 'verified', 'role:cliente'])
        ->prefix('cliente')
        ->name('client.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\DashboardController::class, 'index'])
                ->name('dashboard');
        });

    require __DIR__ . '/auth.php';
});
