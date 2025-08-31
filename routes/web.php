<?php

use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\LessonBookingController;
use App\Http\Controllers\LessonCalendarController;
use App\Http\Controllers\Operator\AvailabilityController as OperatorAvailabilityController;
use App\Http\Controllers\Admin\AvailabilityController as AdminAvailabilityController;
use App\Http\Controllers\Operator\AvailabilityChangeRequestController as OperatorAvailabilityChangeRequestController;
use App\Http\Controllers\Admin\AvailabilityChangeRequestController as AdminAvailabilityChangeRequestController;
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
    // ROOMS AND MACHINES
    //------------------------------
    Route::middleware(['auth', 'verified'])
        ->group(function () {
            Route::resource('/sale', \App\Http\Controllers\RoomController::class)
                ->only(['index', 'show'])
                ->names('rooms')
                ->parameters(['sale' => 'room']);

            Route::resource('/macchine', \App\Http\Controllers\MachineController::class)
                ->only(['index', 'show'])
                ->names('machines')
                ->parameters(['macchine' => 'machine']);
        });

    //------------------------------
    // PACKAGES
    //------------------------------
    Route::middleware(['auth', 'verified'])
        ->group(function () {
            Route::resource('/pacchetti', \App\Http\Controllers\PackageController::class)
                ->only(['index', 'show'])
                ->names('packages')
                ->parameters(['pacchetti' => 'package']);
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

            Route::get('/disponibilita-settimanale/richieste', [AdminAvailabilityChangeRequestController::class, 'index'])
                ->name('availability.requests.index');

            Route::get('/disponibilita-settimanale/richieste/{acr}', [AdminAvailabilityChangeRequestController::class, 'show'])
                ->name('availability.requests.show');

            Route::post('/disponibilita-settimanale/richieste/{acr}/approve', [AdminAvailabilityChangeRequestController::class, 'approve'])
                ->name('availability.requests.approve');

            Route::post('/disponibilita-settimanale/richieste/{acr}/reject', [AdminAvailabilityChangeRequestController::class, 'reject'])
                ->name('availability.requests.reject');

            Route::resource('/sale', \App\Http\Controllers\RoomController::class)
                ->names('rooms')
                ->parameters(['sale' => 'room']);

            Route::resource('/macchine', \App\Http\Controllers\MachineController::class)
                ->names('machines')
                ->parameters(['macchine' => 'machine']);

            Route::resource('/pacchetti', \App\Http\Controllers\PackageController::class)
                ->names('packages')
                ->parameters(['pacchetti' => 'package']);
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

            Route::get('/disponibilita-settimanale/richieste/create', [OperatorAvailabilityChangeRequestController::class, 'create'])
                ->name('availability.requests.create');

            Route::post('/disponibilita-settimanale/richieste', [OperatorAvailabilityChangeRequestController::class, 'store'])
                ->name('availability.requests.store');

            Route::get('/disponibilita-settimanale/richieste', [OperatorAvailabilityChangeRequestController::class, 'index'])
                ->name('availability.requests.index');

            Route::get('/disponibilita-settimanale/richieste/{acr}', [OperatorAvailabilityChangeRequestController::class, 'show'])
                ->name('availability.requests.show');
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
