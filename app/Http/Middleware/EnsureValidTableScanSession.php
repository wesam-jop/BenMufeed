<?php

namespace App\Http\Middleware;

use App\Services\TableScanSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTableScanSession
{
    public function __construct(
        protected TableScanSessionService $scanSessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = (string) config('store.table_scan_cookie', 'bm_ts');
        $token = (string) $request->cookie($cookieName, '');

        $session = $this->scanSessions->findValidByToken($token);

        if ($session === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'انتهت جلسة الطاولة — امسح الباركود من جديد.',
                    'scan_required' => true,
                ], 403);
            }

            return redirect()
                ->route('table.scan-required')
                ->withCookie(cookie()->forget($cookieName));
        }

        $this->scanSessions->touch($session);

        $request->attributes->set('tableScanSession', $session);

        return $next($request);
    }
}
