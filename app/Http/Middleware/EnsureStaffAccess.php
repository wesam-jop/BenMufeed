<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure staff-only areas reject authenticated customers.
 */
class EnsureStaffAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('customer')->check() && ! auth('web')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('toast.forbidden')], 403);
            }

            return redirect()->route('customer.home');
        }

        return $next($request);
    }
}
