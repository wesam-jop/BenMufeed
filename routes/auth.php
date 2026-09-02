<?php

use App\Http\Controllers\Customer\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.store');

    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
});

Route::redirect('/customer/login', '/auth/login');
