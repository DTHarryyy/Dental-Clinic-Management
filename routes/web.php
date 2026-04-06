<?php

use Illuminate\Support\Facades\Route;


Route::get('/', fn () => redirect('/book-appointment'));

Route::get('/login', fn () => view('auth.login'))->name('login');
Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('forgot-password');

Route::get('/book-appointment', fn () => view('public.book-appointment'))->name('public.book');
Route::get('/book-appointment/success', fn () => view('public.book-success'))->name('public.book.success');

Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

Route::prefix('patients')->name('patients.')->group(function () {
    Route::get('/',            fn () => view('patients.index'))->name('index');
    Route::get('/create',      fn () => view('patients.create'))->name('create');
    Route::get('/{id}',        fn () => view('patients.show',  ['id' => request()->route('id')]))->name('show');
    Route::get('/{id}/edit',   fn () => view('patients.edit',  ['id' => request()->route('id')]))->name('edit');
});

Route::prefix('appointments')->name('appointments.')->group(function () {
    Route::get('/',        fn () => view('appointments.index'))->name('index');
    Route::get('/create',  fn () => view('appointments.create'))->name('create');
});

Route::prefix('records')->name('records.')->group(function () {
    Route::get('/',        fn () => view('records.index'))->name('index');
    Route::get('/create',  fn () => view('records.create'))->name('create');
    Route::get('/{id}',    fn () => view('records.show', ['id' => request()->route('id')]))->name('show');
});

Route::prefix('billing')->name('billing.')->group(function () {
    Route::get('/',           fn () => view('billing.index'))->name('index');
    Route::get('/create',     fn () => view('billing.create'))->name('create');
    Route::get('/{id}/receipt', fn () => view('billing.receipt', ['id' => request()->route('id')]))->name('receipt');
});

Route::get('/reports', fn () => view('reports.index'))->name('reports');

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/',          fn () => view('users.index'))->name('index');
    Route::get('/create',    fn () => view('users.create'))->name('create');
    Route::get('/{id}/edit', fn () => view('users.edit', ['id' => request()->route('id')]))->name('edit');
});

Route::get('/settings', fn () => view('settings.index'))->name('settings');
