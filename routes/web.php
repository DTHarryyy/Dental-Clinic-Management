<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

if (app()->environment('e2e')) {
    Route::get('/__e2e/login/{role}', function (string $role) {
        abort_unless(hash_equals((string) env('E2E_AUTH_TOKEN'), (string) request('token')), 404);
        abort_unless(in_array($role, ['admin', 'dentist', 'receptionist'], true), 404);

        $user = \App\Models\User::query()->where('role', $role)->where('status', 'active')->firstOrFail();
        auth()->login($user);

        return redirect()->route('dashboard');
    })->name('e2e.login');
}

Route::get('/', fn () => redirect()->route('public.book'));

// Public booking (no auth required)
Route::get('/book-appointment', [PublicBookingController::class, 'create'])->name('public.book');
Route::post('/book-appointment', [PublicBookingController::class, 'store'])->name('public.book.store');
Route::get('/book-appointment/availability', [PublicBookingController::class, 'availability'])->name('public.book.availability');
Route::get('/book-appointment/success', fn () => view('public.book-success'))->name('public.book.success');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('forgot-password');
Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');

// Authenticated staff/doctor area
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', SearchController::class)->middleware('throttle:120,1')->name('search.index');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'updateDetails'])->name('profile.details.update');
    Route::put('/profile/security', [ProfileController::class, 'updateSecurity'])->name('profile.security.update');
    Route::get('/lookups/patients', [PatientController::class, 'lookup'])->name('lookups.patients');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/{notification}/open', [NotificationController::class, 'open'])->name('open');
        Route::patch('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
        Route::patch('/{notification}/read', [NotificationController::class, 'read'])->name('read');
    });

    Route::prefix('patients')->name('patients.')->group(function () {
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/create', [PatientController::class, 'create'])->name('create');
        Route::post('/', [PatientController::class, 'store'])->name('store');
        Route::get('/{patient}/detail-frame', [PatientController::class, 'detailFrame'])->name('detail-frame');
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
        Route::get('/{appointment}/availability', [AppointmentController::class, 'availability'])->name('availability');
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
        Route::get('/{invoice}/details', [BillingController::class, 'details'])->name('details');
        Route::get('/{invoice}/receipt', [BillingController::class, 'receipt'])->name('receipt');
        Route::post('/{invoice}/payments', [BillingController::class, 'recordPayment'])->name('payments.store');
        Route::post('/{invoice}/send', [BillingController::class, 'sendDocument'])->name('send');
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
            Route::get('/clinic', [SettingsController::class, 'clinic'])->name('clinic');
            Route::put('/clinic', [SettingsController::class, 'updateClinic'])->name('clinic.update');
            Route::get('/services', [SettingsController::class, 'services'])->name('services');
            Route::post('/services', [SettingsController::class, 'storeService'])->name('services.store');
            Route::put('/services/{service}', [SettingsController::class, 'updateService'])->name('services.update');
            Route::delete('/services/{service}', [SettingsController::class, 'destroyService'])->name('services.destroy');
        });
    });
});
