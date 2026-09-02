<?php

use App\Http\Controllers\POS\PosController;
use App\Http\Controllers\POS\PosDeliveryController;
use App\Http\Controllers\POS\PosInvoiceController;
use App\Http\Controllers\POS\PosOrderController;
use App\Http\Controllers\POS\PosSessionController;
use App\Http\Controllers\POS\PosTableController;
use App\Http\Controllers\POS\PosTableOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['staff', 'auth:web', 'verified'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('terminal');
    Route::get('/products', [PosController::class, 'products'])->name('products');

    Route::redirect('/open-session', '/pos')->name('sessions.open-form');

    Route::get('/daily-summary', [PosSessionController::class, 'showDailySummary'])->name('sessions.daily-summary');

    Route::get('/invoices/{invoice}/edit', [PosInvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [PosInvoiceController::class, 'update'])->name('invoices.update');
    Route::get('/invoices/{invoice}/print', [PosInvoiceController::class, 'print'])->name('invoices.print');
    Route::delete('/invoices/{invoice}', [PosInvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::redirect('/daily-close', '/pos/daily-summary');
    Route::post('/daily-close', fn () => redirect()->route('pos.sessions.daily-summary'));

    Route::post('/orders', [PosOrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/items', [PosOrderController::class, 'addItem'])->name('orders.items.store');
    Route::patch('/orders/{order}/items/{item}', [PosOrderController::class, 'updateItem'])->name('orders.items.update');
    Route::delete('/orders/{order}/items/{item}', [PosOrderController::class, 'removeItem'])->name('orders.items.destroy');
    Route::patch('/orders/{order}/discount', [PosOrderController::class, 'applyDiscount'])->name('orders.discount');
    Route::post('/orders/{order}/complete', [PosOrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/send-to-kitchen', [PosOrderController::class, 'sendToKitchen'])->name('orders.send-to-kitchen');

    Route::post('/delivery-orders/{order}/accept', [PosDeliveryController::class, 'accept'])->name('delivery.accept');
    Route::post('/delivery-orders/{order}/reject', [PosDeliveryController::class, 'reject'])->name('delivery.reject');

    Route::post('/table-orders/{order}/accept', [PosTableOrderController::class, 'accept'])->name('table-orders.accept');
    Route::post('/table-orders/{order}/reject', [PosTableOrderController::class, 'reject'])->name('table-orders.reject');

    Route::get('/tables', [PosTableController::class, 'index'])->name('tables.index');
    Route::get('/tables/{table}/order', [PosTableController::class, 'order'])->name('tables.order');
});
