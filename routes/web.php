<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('transactions', 'transactions')
    ->middleware(['auth', 'verified'])
    ->name('transactions.index');

Route::view('personal-funds', 'personal-funds')
    ->middleware(['auth', 'verified'])
    ->name('personal-funds.index');

require __DIR__.'/settings.php';
