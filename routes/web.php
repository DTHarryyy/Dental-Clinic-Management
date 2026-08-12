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
use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
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

// Authenticated staff/doctor area. Every business route declares an explicit ability.
Route::middleware(['auth', 'active.staff', 'audit.denials'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/search', SearchController::class)->middleware(['can:search.use', 'throttle:120,1'])->name('search.index');

    Route::get('/profile', [ProfileController::class, 'show'])->middleware('can:profile.view')->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'updateDetails'])->middleware('can:profile.update')->name('profile.details.update');
    Route::put('/profile/security', [ProfileController::class, 'updateSecurity'])->middleware('can:profile.update')->name('profile.security.update');
    Route::get('/lookups/patients', [PatientController::class, 'lookup'])->can('viewAny', Patient::class)->name('lookups.patients');

    Route::middleware('can:notifications.manage')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/{notification}/open', [NotificationController::class, 'open'])->name('open');
        Route::patch('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
        Route::patch('/{notification}/read', [NotificationController::class, 'read'])->name('read');
    });

    Route::prefix('patients')->name('patients.')->group(function () {
        Route::get('/', [PatientController::class, 'index'])->can('viewAny', Patient::class)->name('index');
        Route::get('/create', [PatientController::class, 'create'])->can('create', Patient::class)->name('create');
        Route::post('/', [PatientController::class, 'store'])->can('create', Patient::class)->name('store');
        Route::get('/{patient}/detail-frame', [PatientController::class, 'detailFrame'])->can('view', 'patient')->name('detail-frame');
        Route::get('/{patient}', [PatientController::class, 'show'])->can('view', 'patient')->name('show');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])->can('view', 'patient')->name('edit');
        Route::patch('/{patient}/demographics', [PatientController::class, 'updateDemographics'])->can('updateDemographics', 'patient')->name('demographics.update');
        Route::patch('/{patient}/clinical', [PatientController::class, 'updateClinical'])->can('updateClinical', 'patient')->name('clinical.update');
        Route::patch('/{patient}/status', [PatientController::class, 'updateStatus'])->can('changeStatus', 'patient')->name('status.update');
    });

    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->can('viewAny', Appointment::class)->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])->can('create', Appointment::class)->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->can('create', Appointment::class)->name('store');
        Route::post('/{appointment}/status', [AppointmentController::class, 'updateStatus'])->can('manage', 'appointment')->name('status');
        Route::get('/{appointment}/availability', [AppointmentController::class, 'availability'])->can('manage', 'appointment')->name('availability');
    });

    Route::prefix('records')->name('records.')->group(function () {
        Route::get('/', [RecordController::class, 'index'])->can('viewAny', DentalRecord::class)->name('index');
        Route::get('/create', [RecordController::class, 'create'])->can('create', DentalRecord::class)->name('create');
        Route::post('/', [RecordController::class, 'store'])->can('create', DentalRecord::class)->name('store');
        Route::get('/{record}', [RecordController::class, 'show'])->can('view', 'record')->name('show');
    });

    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->can('viewAny', Invoice::class)->name('index');
        Route::get('/create', [BillingController::class, 'create'])->can('create', Invoice::class)->name('create');
        Route::post('/', [BillingController::class, 'store'])->can('create', Invoice::class)->name('store');
        Route::get('/{invoice}/details', [BillingController::class, 'details'])->can('view', 'invoice')->name('details');
        Route::get('/{invoice}/receipt', [BillingController::class, 'receipt'])->can('view', 'invoice')->name('receipt');
        Route::post('/{invoice}/payments', [BillingController::class, 'recordPayment'])->can('manage', 'invoice')->name('payments.store');
        Route::post('/{invoice}/send', [BillingController::class, 'sendDocument'])->can('manage', 'invoice')->name('send');
    });

    Route::get('/reports', [ReportController::class, 'index'])->middleware('can:reports.view')->name('reports');
    Route::get('/reports/export/pdf', [ReportController::class, 'pdf'])->middleware('can:reports.export')->name('reports.export.pdf');
    Route::get('/reports/export/csv/{dataset}', [ReportController::class, 'csv'])->middleware('can:reports.export')->name('reports.export.csv');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->can('viewAny', User::class)->name('index');
        Route::get('/create', [UserController::class, 'create'])->can('create', User::class)->name('create');
        Route::post('/', [UserController::class, 'store'])->can('create', User::class)->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->can('view', 'user')->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->can('update', 'user')->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->can('delete', 'user')->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->middleware('can:settings.view')->name('index');
        Route::get('/clinic', [SettingsController::class, 'clinic'])->middleware('can:settings.view')->name('clinic');
        Route::put('/clinic', [SettingsController::class, 'updateClinic'])->middleware('can:settings.manage')->name('clinic.update');
        Route::get('/services', [SettingsController::class, 'services'])->middleware('can:settings.view')->name('services');
        Route::post('/services', [SettingsController::class, 'storeService'])->middleware('can:settings.manage')->name('services.store');
        Route::put('/services/{service}', [SettingsController::class, 'updateService'])->can('update', 'service')->name('services.update');
        Route::delete('/services/{service}', [SettingsController::class, 'destroyService'])->can('delete', 'service')->name('services.destroy');
    });
});
