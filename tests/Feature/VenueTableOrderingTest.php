<?php

namespace Tests\Feature;

use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\TableScanSession;
use App\Services\TableScanSessionService;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class VenueTableOrderingTest extends TestCase
{
    private function venueCookieName(): string
    {
        return (string) config('store.table_scan_cookie', 'bm_ts');
    }

    private function extractCookie($response, string $name): ?Cookie
    {
        return collect($response->headers->getCookies())->firstWhere('getName', $name)
            ?? collect($response->headers->getCookies())->first(fn (Cookie $c) => $c->getName() === $name);
    }

    public function test_direct_table_access_without_scan_redirects_to_scan_required(): void
    {
        $this->get('/table')->assertRedirect(route('table.scan-required'));
        $this->get('/table/menu')->assertRedirect(route('table.scan-required'));
    }

    public function test_scan_required_page_is_public(): void
    {
        $this->get('/table/scan-required')->assertOk();
    }

    public function test_venue_barcode_issues_session_and_requires_table_selection(): void
    {
        $settings = SiteSetting::current();
        $token = $settings->venueScanToken();

        RestaurantTable::query()->create([
            'number' => 5,
            'qr_token' => 'venue-flow-table-token-1234567890123456789012345',
            'is_active' => true,
        ]);

        $scan = $this->get('/t/'.$token);
        $scan->assertRedirect(route('table.index'));

        $cookie = $this->extractCookie($scan, $this->venueCookieName());
        $this->assertNotNull($cookie);

        // جلسة موحّدة بلا طاولة بعد.
        $this->assertDatabaseHas('table_scan_sessions', ['restaurant_table_id' => null]);

        // صفحة اختيار الطاولة تُعرض.
        $this->withCookie($this->venueCookieName(), $cookie->getValue())
            ->get('/table')
            ->assertOk();

        // اختيار الطاولة يربطها بالجلسة.
        $this->withCookie($this->venueCookieName(), $cookie->getValue())
            ->post('/table/select', ['table_number' => 5])
            ->assertRedirect(route('table.menu'));

        $this->assertSame(1, TableScanSession::query()->whereNotNull('restaurant_table_id')->count());

        // القائمة تُعرض بعد الاختيار.
        $this->withCookie($this->venueCookieName(), $cookie->getValue())
            ->get('/table/menu')
            ->assertOk();
    }

    public function test_occupied_table_cannot_be_selected(): void
    {
        $settings = SiteSetting::current();
        $token = $settings->venueScanToken();

        $table = RestaurantTable::query()->create([
            'number' => 7,
            'qr_token' => 'occupied-table-token-1234567890123456789012345678',
            'is_active' => true,
        ]);

        // طلب مفتوح على الطاولة.
        \App\Models\Order::query()->create([
            'status' => \App\Enums\OrderStatus::Open,
            'source' => \App\Enums\OrderSource::Website,
            'service_type' => \App\Enums\PosServiceType::Table,
            'table_number' => $table->number,
            'restaurant_table_id' => $table->getKey(),
            'total' => 0,
            'customer_name' => $table->displayName(),
        ]);

        $scan = $this->get('/t/'.$token);
        $cookie = $this->extractCookie($scan, $this->venueCookieName());

        $this->withCookie($this->venueCookieName(), $cookie->getValue())
            ->post('/table/select', ['table_number' => 7])
            ->assertSessionHasErrors('table_number');
    }

    public function test_expired_session_redirects_to_scan_required(): void
    {
        $settings = SiteSetting::current();
        $token = $settings->venueScanToken();

        RestaurantTable::query()->create([
            'number' => 9,
            'qr_token' => 'expired-table-token-12345678901234567890123456789',
            'is_active' => true,
        ]);

        $scan = $this->get('/t/'.$token);
        $cookie = $this->extractCookie($scan, $this->venueCookieName());

        TableScanSession::query()->update(['expires_at' => now()->subHour()]);

        $this->withCookie($this->venueCookieName(), $cookie->getValue())
            ->get('/table')
            ->assertRedirect(route('table.scan-required'));

        // الجلسة المنتهية حُذفت.
        $this->assertSame(0, TableScanSession::query()->count());
    }

    public function test_legacy_table_barcode_preselects_table(): void
    {
        SiteSetting::current();

        $table = RestaurantTable::query()->create([
            'number' => 3,
            'qr_token' => 'legacydirecttabletoken123456789012345678901234',
            'is_active' => true,
        ]);

        $scan = $this->get('/t/'.$table->qr_token);
        $scan->assertRedirect(route('table.menu'));

        $this->assertDatabaseHas('table_scan_sessions', [
            'restaurant_table_id' => $table->getKey(),
        ]);
    }
}
