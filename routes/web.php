<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentChangeRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientAccountLinkRequestController;
use App\Http\Controllers\PatientAppointmentController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientBillingController;
use App\Http\Controllers\PatientDashboardController;
use App\Http\Controllers\PatientNotificationController;
use App\Http\Controllers\PatientProfileController;
use App\Http\Controllers\PatientRegistrationController;
use App\Http\Controllers\PatientTreatmentController;
use App\Http\Controllers\PatientVerificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicSiteController;
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
        abort_unless(in_array($role, ['admin', 'dentist', 'receptionist', 'patient'], true), 404);

        $user = \App\Models\User::query()->where('role', $role)->where('status', 'active')->firstOrFail();
        auth()->login($user);

        return redirect()->route($role === 'patient' ? 'patient.dashboard' : 'dashboard');
    })->name('e2e.login');
}

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/privacy', [PublicSiteController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PublicSiteController::class, 'terms'])->name('terms');

// Compatibility gateway for old booking links. Verified patients use the portal.
Route::get('/book-appointment', [PublicBookingController::class, 'create'])->name('public.book');
Route::post('/book-appointment', [PublicBookingController::class, 'store'])->name('public.book.store');
Route::get('/book-appointment/availability', [PublicBookingController::class, 'availability'])->name('public.book.availability');
Route::get('/book-appointment/success', fn () => view('public.book-success'))->name('public.book.success');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [PatientRegistrationController::class, 'create'])->name('register');
Route::post('/register', [PatientRegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
Route::get('/verify-email', [PatientVerificationController::class, 'show'])->name('verify-email');
Route::get('/verify-email/confirm', [PatientVerificationController::class, 'confirm'])->name('verify-email.confirm');
Route::post('/verify-email/confirm', [PatientVerificationController::class, 'consume'])->name('verify-email.consume');
Route::post('/verify-email/resend', [PatientVerificationController::class, 'resend'])->middleware('throttle:3,10')->name('verify-email.resend');
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

    Route::prefix('patient-accounts')->name('patient-accounts.')->group(function () {
        Route::get('/', [PatientAccountLinkRequestController::class, 'index'])->middleware('can:patient-accounts.view')->name('index');
        Route::patch('/{linkRequest}', [PatientAccountLinkRequestController::class, 'resolve'])->middleware('can:patient-accounts.manage')->name('resolve');
    });

    Route::prefix('appointment-change-requests')->name('appointment-change-requests.')->group(function () {
        Route::get('/', [AppointmentChangeRequestController::class, 'index'])->middleware('can:appointment-change-requests.view')->name('index');
        Route::patch('/{changeRequest}', [AppointmentChangeRequestController::class, 'resolve'])->middleware('can:appointment-change-requests.manage')->name('resolve');
    });

    Route::prefix('records')->name('records.')->group(function () {
        Route::get('/', [RecordController::class, 'index'])->can('viewAny', DentalRecord::class)->name('index');
        Route::get('/create', [RecordController::class, 'create'])->can('create', DentalRecord::class)->name('create');
        Route::post('/', [RecordController::class, 'store'])->can('create', DentalRecord::class)->name('store');
        Route::get('/{record}', [RecordController::class, 'show'])->can('view', 'record')->name('show');
        Route::post('/{record}/publish', [RecordController::class, 'publish'])->can('publish', 'record')->name('publish');
        Route::delete('/{record}/publish', [RecordController::class, 'unpublish'])->can('publish', 'record')->name('unpublish');
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
        Route::get('/public-website', [SettingsController::class, 'publicWebsite'])->middleware('can:settings.view')->name('public');
        Route::put('/public-website', [SettingsController::class, 'updatePublicWebsite'])->middleware('can:settings.manage')->name('public.update');
        Route::get('/business-hours', [SettingsController::class, 'businessHours'])->middleware('can:settings.view')->name('hours');
        Route::put('/business-hours', [SettingsController::class, 'updateBusinessHours'])->middleware('can:settings.manage')->name('hours.update');
        Route::get('/closures', [SettingsController::class, 'closures'])->middleware('can:settings.view')->name('closures');
        Route::post('/closures', [SettingsController::class, 'storeClosure'])->middleware('can:settings.manage')->name('closures.store');
        Route::delete('/closures/{closure}', [SettingsController::class, 'destroyClosure'])->middleware('can:settings.manage')->name('closures.destroy');
        Route::get('/services', [SettingsController::class, 'services'])->middleware('can:settings.view')->name('services');
        Route::post('/services', [SettingsController::class, 'storeService'])->middleware('can:settings.manage')->name('services.store');
        Route::put('/services/{service}', [SettingsController::class, 'updateService'])->can('update', 'service')->name('services.update');
        Route::delete('/services/{service}', [SettingsController::class, 'destroyService'])->can('delete', 'service')->name('services.destroy');
        Route::get('/team', [SettingsController::class, 'team'])->middleware('can:settings.view')->name('team');
        Route::post('/team', [SettingsController::class, 'storeTeam'])->middleware('can:settings.manage')->name('team.store');
        Route::put('/team/{profile}', [SettingsController::class, 'updateTeam'])->middleware('can:settings.manage')->name('team.update');
        Route::delete('/team/{profile}', [SettingsController::class, 'destroyTeam'])->middleware('can:settings.manage')->name('team.destroy');
        Route::get('/faqs', [SettingsController::class, 'faqs'])->middleware('can:settings.view')->name('faqs');
        Route::post('/faqs', [SettingsController::class, 'storeFaq'])->middleware('can:settings.manage')->name('faqs.store');
        Route::put('/faqs/{faq}', [SettingsController::class, 'updateFaq'])->middleware('can:settings.manage')->name('faqs.update');
        Route::delete('/faqs/{faq}', [SettingsController::class, 'destroyFaq'])->middleware('can:settings.manage')->name('faqs.destroy');
    });
});

Route::middleware(['auth', 'active.patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/account-review', [PatientAccountLinkRequestController::class, 'reviewStatus'])->name('account-review');

    Route::middleware('linked.patient')->group(function () {
        Route::get('/dashboard', PatientDashboardController::class)->name('dashboard');
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [PatientAppointmentController::class, 'index'])->name('index');
            Route::get('/create', [PatientAppointmentController::class, 'create'])->name('create');
            Route::post('/', [PatientAppointmentController::class, 'store'])->name('store');
            Route::get('/dates', [PatientAppointmentController::class, 'dates'])->middleware('throttle:120,1')->name('dates');
            Route::get('/slots', [PatientAppointmentController::class, 'slots'])->middleware('throttle:120,1')->name('slots');
            Route::get('/{appointment}', [PatientAppointmentController::class, 'show'])->name('show');
            Route::patch('/{appointment}/withdraw', [PatientAppointmentController::class, 'withdraw'])->name('withdraw');
            Route::post('/{appointment}/change-request', [PatientAppointmentController::class, 'requestChange'])->name('change');
        });
        Route::get('/treatments', [PatientTreatmentController::class, 'index'])->name('treatments.index');
        Route::get('/treatments/{record}', [PatientTreatmentController::class, 'show'])->name('treatments.show');
        Route::get('/billing', [PatientBillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/{invoice}', [PatientBillingController::class, 'show'])->name('billing.show');
        Route::get('/billing/{invoice}/receipt', [PatientBillingController::class, 'receipt'])->name('billing.receipt');
        Route::get('/notifications', [PatientNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/open', [PatientNotificationController::class, 'open'])->name('notifications.open');
        Route::patch('/notifications/read-all', [PatientNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [PatientNotificationController::class, 'read'])->name('notifications.read');
        Route::get('/profile', [PatientProfileController::class, 'show'])->name('profile');
        Route::patch('/profile', [PatientProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/security', [PatientProfileController::class, 'security'])->name('profile.security');
    });
});
