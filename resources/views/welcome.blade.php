<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));
Route::get('/dashboard', fn () => view('dashboard'));