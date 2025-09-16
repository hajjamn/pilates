<?php

use App\Http\Controllers\Client\ClientLessonController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\LessonBookingController;
use App\Http\Controllers\LessonCalendarController;
use App\Http\Controllers\LessonManageController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\Operator\AvailabilityController as OperatorAvailabilityController;
use App\Http\Controllers\Admin\AvailabilityController as AdminAvailabilityController;
use App\Http\Controllers\Operator\AvailabilityChangeRequestController as OperatorAvailabilityChangeRequestController;
use App\Http\Controllers\Admin\AvailabilityChangeRequestController as AdminAvailabilityChangeRequestController;
use App\Http\Controllers\Operator\ClientController;
use App\Http\Controllers\Operator\OperatorController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserPackageController as AdminUserPackageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::group([], function () {

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

        Route::middleware(['role:operatore|admin'])->group(function () {

            Route::post('/lessons/{lesson}/bookings', [LessonBookingController::class, 'storeManaged'])
                ->name('bookings.store');

            Route::post('/bookings/{booking}/toggle-attended', [LessonBookingController::class, 'toggleAttended'])
                ->name('bookings.toggleAttended');

            Route::post('/bookings/{booking}/toggle-paid', [LessonBookingController::class, 'togglePaid'])
                ->name('bookings.togglePaid');

            Route::post('/bookings/{booking}/toggle-contacted', [LessonBookingController::class, 'toggleContacted'])
                ->name('bookings.toggleContacted');

            Route::get('/clients/search', [LessonBookingController::class, 'searchClients'])
                ->name('clients.search');
        });
    });

    //------------------------------
    // LESSON MANAGE
    //------------------------------
    Route::middleware(['auth', 'verified', 'role:operatore|admin'])->group(function () {
        // crea lezione manuale

        Route::get('/lessons/create', [LessonManageController::class, 'create'])
            ->name('lessons.create')
            ->middleware('role:admin');


        Route::get('/lessons/create-lite', [LessonManageController::class, 'createLite'])
            ->name('lessons.createLite');


        Route::post('/lessons', [LessonManageController::class, 'store'])
            ->name('lessons.store');

        // annulla / ripristina
        Route::post('/lessons/{lesson}/cancel', [LessonManageController::class, 'cancel'])
            ->name('lessons.cancel');
        Route::post('/lessons/{lesson}/uncancel', [LessonManageController::class, 'uncancel'])
            ->name('lessons.uncancel');

        // opzionale: elimina record (uso amministrativo “hard delete”)
        Route::delete('/lessons/{lesson}', [LessonManageController::class, 'destroy'])
            ->name('lessons.destroy')
            ->middleware('role:admin');

        Route::get('/lessons/{lesson}', [LessonManageController::class, 'show'])
            ->name('lessons.show');

        // Edit completa (admin)
        Route::get('/lessons/{lesson}/edit', [LessonManageController::class, 'edit'])
            ->name('lessons.edit');

        // Edit limitata (operatore)
        Route::get('/lessons/{lesson}/edit-lite', [LessonManageController::class, 'editLite'])
            ->name('lessons.editLite');

        // Update (stessa rotta per entrambi; i permessi/campi saranno gestiti nel controller)
        Route::patch('/lessons/{lesson}', [LessonManageController::class, 'update'])
            ->name('lessons.update');

        Route::patch('/lessons/{lesson}/lite', [LessonManageController::class, 'updateLite'])
            ->name('lessons.updateLite')
            ->whereNumber('lesson');

    });



    //------------------------------
    // ROOMS AND MACHINES
    //------------------------------
    Route::middleware(['auth', 'verified'])
        ->group(function () {
            Route::resource('/sale', RoomController::class)
                ->only(['index', 'show'])
                ->names('rooms')
                ->parameters(['sale' => 'room']);

            Route::resource('/macchine', MachineController::class)
                ->only(['index', 'show'])
                ->names('machines')
                ->parameters(['macchine' => 'machine']);
        });

    //------------------------------
    // PACKAGES
    //------------------------------
    Route::middleware(['auth', 'verified'])
        ->group(function () {
            Route::resource('/pacchetti', PackageController::class)
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

            Route::get('/disponibilita-settimanale', [AdminAvailabilityController::class, 'index'])
                ->name('availability.index');

            Route::get('/disponibilita-settimanale/genera', [AdminAvailabilityController::class, 'showGenerate'])
                ->name('availability.generate.form');

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

            Route::resource('/sale', RoomController::class)
                ->names('rooms')
                ->parameters(['sale' => 'room']);

            Route::resource('/macchine', MachineController::class)
                ->names('machines')
                ->parameters(['macchine' => 'machine']);

            Route::resource('/pacchetti', PackageController::class)
                ->names('packages')
                ->parameters(['pacchetti' => 'package']);

            Route::resource('/utenti', AdminUserController::class)
                ->only(['index', 'show'])
                ->names('users')
                ->parameters(['utenti' => 'user']);

            Route::post('/utenti/{user}/packages', [AdminUserPackageController::class, 'store'])
                ->name('users.packages.store');
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

            Route::get('/clienti/create', [ClientController::class, 'create'])
                ->name('clients.create');

            Route::post('/clienti', [ClientController::class, 'store'])
                ->name('clients.store');
        });

    //------------------------------
    // CLIENT AREA
    //------------------------------
    Route::middleware(['auth', 'verified', 'role:cliente'])
        ->prefix('cliente')
        ->name('client.')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/lezioni/{lesson}', [ClientLessonController::class, 'show'])
                ->whereNumber('lesson')
                ->name('lessons.show');

            Route::get('/lezioni', [ClientLessonController::class, 'index'])
                ->name('lessons.index');
        });

    require __DIR__ . '/auth.php';
});