<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\RestaurantTableService;
use App\Services\TableScanSessionService;
use Illuminate\Http\RedirectResponse;

class VenueScanEntryController extends Controller
{
    public function __invoke(
        string $token,
        RestaurantTableService $tables,
        TableScanSessionService $scanSessions,
    ): RedirectResponse {
        $normalized = $tables->normalizeToken($token);
        $settings = SiteSetting::current();
        $cookieName = (string) config('store.table_scan_cookie', 'bm_ts');
        $lifetimeMinutes = max(60, (int) config('store.table_scan_max_hours', 4) * 60);

        // الباركود الموحّد لكل الطاولات.
        if ($normalized !== '' && hash_equals((string) $settings->venueScanToken(), $normalized)) {
            $session = $scanSessions->issueVenueSession();

            return redirect()
                ->route('table.index')
                ->withCookie(cookie($cookieName, $session->session_token, $lifetimeMinutes, null, null, null, true));
        }

        // توافق خلفي: باركود طاولة محدّد → جلسة موحّدة مربوطة بتلك الطاولة.
        $table = $tables->findByToken($normalized);

        if ($table !== null) {
            $session = $scanSessions->issueVenueSession();
            $scanSessions->attachTable($session, $table);

            return redirect()
                ->route('table.menu')
                ->withCookie(cookie($cookieName, $session->session_token, $lifetimeMinutes, null, null, null, true));
        }

        return redirect()->route('table.scan-required');
    }
}
