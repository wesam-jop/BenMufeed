<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Storefront\StorefrontCartController;
use App\Http\Controllers\Storefront\StorefrontCheckoutController;
use App\Http\Controllers\Storefront\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::controller(StorefrontController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/categories', 'categories')->name('storefront.categories');
    Route::get('/categories/{slug}', 'categoryShow')->name('storefront.categories.show');
    Route::get('/products', 'products')->name('storefront.products');
    Route::get('/about', 'about')->name('storefront.about');
    Route::get('/contact', 'contact')->name('storefront.contact');
    Route::get('/errors/{code}', 'errorPage')
        ->whereNumber('code')
        ->name('storefront.errors');
    Route::get('/checkout/success-preview', 'checkoutSuccessPreview')
        ->name('storefront.checkout.success-preview');
});

Route::post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', ['ar', 'en'])
    ->middleware('throttle:30,1')
    ->name('locale.update');

Route::controller(StorefrontCartController::class)->group(function () {
    Route::get('/cart', 'index')->name('storefront.cart');
    Route::post('/cart/items', 'storeItem')->name('storefront.cart.items.store');
    Route::patch('/cart/items/{productId}', 'updateItem')->name('storefront.cart.items.update');
    Route::delete('/cart/items/{productId}', 'destroyItem')->name('storefront.cart.items.destroy');
    Route::patch('/cart/location', 'updateLocation')->name('storefront.cart.location');
});

Route::controller(StorefrontCheckoutController::class)->group(function () {
    Route::get('/checkout', 'show')->name('storefront.checkout');
    Route::get('/checkout/verify', 'verify')->name('storefront.checkout.verify');
    Route::post('/checkout/otp', 'sendOtp')->middleware('throttle:5,1')->name('storefront.checkout.otp');
    Route::post('/checkout/confirm', 'confirm')->middleware('throttle:10,1')->name('storefront.checkout.confirm');
    Route::get('/checkout/success/{order}', 'success')->name('storefront.checkout.success');
});

Route::middleware(['staff', 'auth:web', 'verified'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');
});

require __DIR__.'/settings.php';
