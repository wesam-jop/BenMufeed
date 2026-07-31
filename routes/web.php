<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartSyncController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CategoryIndexController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\OrderThankYouController;
use App\Http\Controllers\Storefront\StorefrontCustomerAccountController;
use App\Http\Controllers\Storefront\StorefrontCustomerAddressController;
use App\Http\Controllers\Storefront\StorefrontCustomerOtpController;
use App\Http\Controllers\Storefront\TableOrderController;
use App\Http\Controllers\Storefront\TableResolveController;
use App\Http\Controllers\Storefront\VenueScanEntryController;
use App\Http\Controllers\Storefront\StoreNotFoundController;
use App\Http\Controllers\Storefront\StorePageController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ProductIndexController;
use App\Http\Controllers\Storefront\ProductSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/categories', CategoryIndexController::class)->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/products', ProductIndexController::class)->name('products.index');
Route::get('/products/search', ProductSearchController::class)
    ->middleware('throttle:90,1')
    ->name('products.search');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cart', CartController::class)->name('cart');
Route::get('/t/{token}', VenueScanEntryController::class)
    ->where('token', '[A-Za-z0-9]+')
    ->name('tables.entry');
Route::get('/api/tables/resolve/{token}', TableResolveController::class)
    ->middleware('throttle:60,1')
    ->name('api.tables.resolve');

// واجهة طلب الطاولة المنفصلة — محميّة بجلسة مسح الباركود (4 ساعات).
Route::get('/table/scan-required', [TableOrderController::class, 'scanRequired'])
    ->name('table.scan-required');
Route::middleware('table.scan')->group(function (): void {
    Route::get('/table', [TableOrderController::class, 'index'])->name('table.index');
    Route::post('/table/select', [TableOrderController::class, 'select'])->name('table.select');
    Route::get('/table/menu', [TableOrderController::class, 'menu'])->name('table.menu');
    Route::get('/table/cart', [TableOrderController::class, 'cart'])->name('table.cart');
    Route::post('/table/order', [TableOrderController::class, 'order'])
        ->middleware('throttle:20,1')
        ->name('table.order');
});
Route::get('/api/account/me', [StorefrontCustomerAccountController::class, 'me'])
    ->middleware('throttle:60,1')
    ->name('api.account.me');
Route::patch('/api/account/profile', [StorefrontCustomerAccountController::class, 'updateProfile'])
    ->middleware('throttle:30,1')
    ->name('api.account.profile');
Route::post('/api/account/otp/send', [StorefrontCustomerOtpController::class, 'send'])
    ->middleware('throttle:10,1')
    ->name('api.account.otp.send');
Route::post('/api/account/otp/verify', [StorefrontCustomerOtpController::class, 'verify'])
    ->middleware('throttle:20,1')
    ->name('api.account.otp.verify');
Route::get('/api/account/addresses', [StorefrontCustomerAddressController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('api.account.addresses');
Route::post('/api/account/addresses', [StorefrontCustomerAddressController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.account.addresses.store');
Route::delete('/api/account/addresses/{address}', [StorefrontCustomerAddressController::class, 'destroy'])
    ->middleware('throttle:30,1')
    ->name('api.account.addresses.destroy');
Route::get('/account/login', [StorefrontCustomerAccountController::class, 'loginPage'])
    ->name('account.login');
Route::get('/account', [StorefrontCustomerAccountController::class, 'page'])
    ->name('account');
Route::post('/cart/sync', CartSyncController::class)
    ->middleware('throttle:60,1')
    ->name('cart.sync');
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('orders.store');
Route::get('/orders/thank-you', OrderThankYouController::class)
    ->name('orders.thank-you');
Route::get('/about', [StorePageController::class, 'about'])->name('pages.about');
Route::get('/shipping', [StorePageController::class, 'shipping'])->name('pages.shipping');
Route::get('/contact', [StorePageController::class, 'contact'])->name('pages.contact');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

require __DIR__.'/auth.php';

Route::redirect('/admin/login', '/login');

require __DIR__.'/pos.php';
require __DIR__.'/invoices.php';

Route::get('/dashboard', function () {
    $user = auth()->user();

    return redirect($user?->homeUrl() ?? '/');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::fallback(StoreNotFoundController::class);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
