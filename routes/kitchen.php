<?php

use App\Http\Controllers\Kitchen\KitchenDisplayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['staff', 'auth:web', 'verified'])->prefix('kitchen')->name('kitchen.')->group(function () {
    Route::get('/', [KitchenDisplayController::class, 'index'])->name('display');
    Route::patch('/orders/{order}/status', [KitchenDisplayController::class, 'updateStatus'])->name('orders.status');
});
