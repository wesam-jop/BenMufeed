<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            env('STOREFRONT_CUSTOMER_COOKIE', 'bm_sc'),
            // رمز جلسة الطلب المفتوح للطاولة (يقرأه العميل لإضافة أصناف لطلبه).
            env('STORE_TABLE_ORDER_COOKIE', 'bm_tos'),
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\CachePublicMediaHeaders::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'pos.access' => \App\Http\Middleware\EnsureUserCanAccessPos::class,
            'kitchen.access' => \App\Http\Middleware\EnsureUserCanAccessKitchen::class,
            'printing' => \App\Http\Middleware\EnsureUserCanUsePrinting::class,
            'table.scan' => \App\Http\Middleware\EnsureValidTableScanSession::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => $request->user()?->homeUrl() ?? '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
