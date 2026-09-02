<?php

namespace App\Http\Middleware;

use App\Services\Shared\PostLoginRedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomer
{
    public function __construct(
        protected PostLoginRedirectService $redirects,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('toast.unauthenticated')], 401);
            }

            if ($staff = auth('web')->user()) {
                return redirect($this->redirects->forUser($staff));
            }

            return redirect()->guest(route('auth.login'));
        }

        return $next($request);
    }
}
