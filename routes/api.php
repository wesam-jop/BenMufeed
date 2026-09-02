<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless JSON endpoints live here. Controllers belong under
| App\Http\Controllers\Api. Keep web/Inertia routes in routes/*.php.
|
*/

Route::middleware('api')
    ->prefix('api')
    ->name('api.')
    ->group(function (): void {
        //
    });
