<?php

namespace App\Http\Middleware;

use App\Services\Sales\TableGuestSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTableGuestSession
{
    public function __construct(
        protected TableGuestSessionService $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie(TableGuestSessionService::COOKIE_NAME)
            ?? $request->bearerToken()
            ?? $request->header('X-Table-Session');

        $session = $this->sessions->findActiveByToken($token);

        if (! $session) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('toast.table_session_invalid'),
                ], 401);
            }

            return redirect()
                ->route('table-order.index')
                ->with('toast', [
                    'type' => 'error',
                    'message' => __('toast.table_session_invalid'),
                ]);
        }

        $request->attributes->set('tableGuestSession', $session);

        return $next($request);
    }
}
