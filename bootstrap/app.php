<?php

use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\EnsureCustomerPasswordIsSet;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/pos.php'));

            Route::middleware('web')
                ->group(base_path('routes/kitchen.php'));

            Route::middleware('web')
                ->group(base_path('routes/customer.php'));

            Route::middleware('web')
                ->get('/{cmsPath}', [\App\Http\Controllers\Storefront\StorefrontController::class, 'cmsPage'])
                ->where('cmsPath', '[a-z0-9]+(?:-[a-z0-9]+)*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)*')
                ->name('storefront.cms');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'locale']);

        $middleware->redirectGuestsTo(function () {
            if (auth('customer')->check()) {
                return route('customer.home');
            }

            return route('auth.login');
        });

        $middleware->prependToPriorityList(
            before: \Illuminate\Auth\Middleware\Authenticate::class,
            prepend: \App\Http\Middleware\EnsureStaffAccess::class,
        );

        $middleware->alias([
            'customer' => AuthenticateCustomer::class,
            'customer.password' => EnsureCustomerPasswordIsSet::class,
            'staff' => \App\Http\Middleware\EnsureStaffAccess::class,
            'table.guest' => \App\Http\Middleware\EnsureTableGuestSession::class,
            'branch' => \App\Http\Middleware\EnsureBranchSelected::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function ($response, \Throwable $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*', 'admin/*', 'pos/*', 'kitchen/*', 'customer/*')) {
                return $response;
            }

            $status = $response->getStatusCode();
            if (! in_array($status, [403, 404, 500], true)) {
                return $response;
            }

            try {
                /** @var \App\Http\Controllers\Storefront\StorefrontController $controller */
                $controller = app(\App\Http\Controllers\Storefront\StorefrontController::class);
                $inertia = $controller->errorPage($status);

                return $inertia->toResponse($request)->setStatusCode($status);
            } catch (\Throwable) {
                return $response;
            }
        });
    })->create();
