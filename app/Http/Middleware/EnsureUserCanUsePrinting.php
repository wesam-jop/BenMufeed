<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanUsePrinting
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if ($user->isAdmin() || $user->canAccessPos() || $user->canAccessKitchen()) {
            return $next($request);
        }

        abort(403);
    }
}
