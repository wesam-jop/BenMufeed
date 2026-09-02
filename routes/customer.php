<?php

use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerAuthController;
use App\Http\Controllers\Customer\CustomerFavoriteController;
use App\Http\Controllers\Customer\CustomerHomeController;
use App\Http\Controllers\Customer\CustomerMenuController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\CustomerPasswordController;
use App\Http\Controllers\Customer\CustomerPasswordResetController;
use App\Http\Controllers\Customer\CustomerProfileController;
use App\Http\Controllers\Customer\QROrderController;
use App\Http\Controllers\Customer\TableOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->name('customer.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register']);

        Route::get('/password/forgot', [CustomerPasswordResetController::class, 'showForgot'])->name('password.forgot');
        Route::post('/password/forgot', [CustomerPasswordResetController::class, 'sendResetLink'])
            ->middleware('throttle:5,1')
            ->name('password.forgot.send');
        Route::get('/password/reset/{token}', [CustomerPasswordResetController::class, 'showReset'])->name('password.reset');
        Route::post('/password/reset/{token}', [CustomerPasswordResetController::class, 'reset'])
            ->middleware('throttle:10,1')
            ->name('password.reset.store');
    });

    Route::post('/logout', [CustomerAuthController::class, 'logout'])
        ->middleware('customer')
        ->name('logout');

    Route::middleware('customer')->group(function () {
        Route::get('/password/setup', [CustomerPasswordController::class, 'show'])->name('password.setup');
        Route::post('/password/setup', [CustomerPasswordController::class, 'store'])->name('password.store');

        Route::post('/favorites', [CustomerFavoriteController::class, 'store'])->name('favorites.store');
        Route::delete('/favorites/{product}', [CustomerFavoriteController::class, 'destroy'])->name('favorites.destroy');
    });

    Route::middleware(['customer', 'customer.password'])->group(function () {
        Route::get('/', [CustomerHomeController::class, 'index'])->name('home');
        Route::get('/menu', [CustomerMenuController::class, 'index'])->name('menu');

        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [CustomerOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');

        Route::get('/addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
        Route::post('/addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
        Route::patch('/addresses/{address}', [CustomerAddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
        Route::post('/addresses/{address}/default', [CustomerAddressController::class, 'setDefault'])->name('addresses.default');

        Route::get('/profile', [CustomerProfileController::class, 'show'])->name('profile');
        Route::patch('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [CustomerProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('/favorites', [CustomerFavoriteController::class, 'index'])->name('favorites.index');
    });
});

Route::prefix('table-order')->name('table-order.')->group(function () {
    Route::get('/', [TableOrderController::class, 'index'])->name('index');
    Route::post('/activate', [TableOrderController::class, 'activate'])->middleware('throttle:10,1')->name('activate');

    Route::middleware('table.guest')->group(function () {
        Route::get('/menu', [TableOrderController::class, 'menu'])->name('menu');
        Route::post('/orders', [TableOrderController::class, 'storeOrder'])->middleware('throttle:20,1')->name('orders.store');
        Route::get('/orders', [TableOrderController::class, 'orders'])->name('orders');
        Route::post('/end', [TableOrderController::class, 'end'])->name('end');
    });
});

Route::get('/order/{qr_token}', [QROrderController::class, 'show'])->name('qr.order');
Route::post('/order/{qr_token}', [QROrderController::class, 'store'])->name('qr.order.store');
