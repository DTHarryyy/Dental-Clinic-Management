<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

Route::view('/dashboard', 'dashboard');

Route::view('/patients', 'dashboard');
Route::view('/appointments', 'dashboard');
Route::view('/treatments', 'dashboard');
Route::view('/billing', 'dashboard');
Route::view('/inventory', 'dashboard');
Route::view('/reports', 'dashboard');
Route::view('/users', 'dashboard');
Route::view('/audit', 'dashboard');
Route::view('/settings', 'dashboard');