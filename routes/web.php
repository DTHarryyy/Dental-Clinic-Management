<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('public.book'));

// Public booking (no auth required)
Route::get('/book-appointment', [PublicBookingController::class, 'create'])->name('public.book');
Route::post('/book-appointment', [PublicBookingController::class, 'store'])->name('public.book.store');
Route::get('/book-appointment/success', fn () => view('public.book-success'))->name('public.book.success');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('forgot-password');

// Authenticated staff/doctor area
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('patients')->name('patients.')->group(function () {
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/create', [PatientController::class, 'create'])->name('create');
        Route::post('/', [PatientController::class, 'store'])->name('store');
        Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
        Route::put('/{patient}', [PatientController::class, 'update'])->name('update');
        Route::post('/{patient}/deactivate', [PatientController::class, 'deactivate'])->name('deactivate');
    });

    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::post('/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('status');
    });

    Route::middleware('role:admin,dentist')->prefix('records')->name('records.')->group(function () {
        Route::get('/', [RecordController::class, 'index'])->name('index');
        Route::get('/create', [RecordController::class, 'create'])->name('create');
        Route::post('/', [RecordController::class, 'store'])->name('store');
        Route::get('/{record}', [RecordController::class, 'show'])->name('show');
    });

    Route::middleware('role:admin,receptionist')->prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::get('/create', [BillingController::class, 'create'])->name('create');
        Route::post('/', [BillingController::class, 'store'])->name('store');
        Route::get('/{invoice}/receipt', [BillingController::class, 'receipt'])->name('receipt');
        Route::post('/{invoice}/mark-paid', [BillingController::class, 'markPaid'])->name('mark-paid');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/clinic', [SettingsController::class, 'updateClinic'])->name('clinic');
            Route::post('/services', [SettingsController::class, 'storeService'])->name('services.store');
            Route::put('/services/{service}', [SettingsController::class, 'updateService'])->name('services.update');
            Route::delete('/services/{service}', [SettingsController::class, 'destroyService'])->name('services.destroy');
        });
    });
});
