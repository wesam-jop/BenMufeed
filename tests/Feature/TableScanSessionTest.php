<?php

namespace Tests\Feature;

use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\TableScanSession;
use App\Services\TableScanSessionService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TableScanSessionTest extends TestCase
{
    public function test_expired_idle_scan_session_is_rejected(): void
    {
        SiteSetting::current();
        Config::set('store.table_scan_idle_minutes', 30);

        $table = RestaurantTable::query()->create([
            'number' => 11,
            'qr_token' => 'idle-scan-table-token-123456789012345678901234567',
            'is_active' => true,
        ]);

        $scan = app(TableScanSessionService::class)->issueFresh($table);
        TableScanSession::query()->whereKey($scan->getKey())->update([
            'last_seen_at' => now()->subMinutes(31),
        ]);

        $this->getJson('/api/tables/resolve/'.$table->qr_token.'?scan_session='.$scan->session_token)
            ->assertForbidden()
            ->assertJsonPath('scan_session_expired', true);
    }

    public function test_bookmark_without_scan_session_requires_qr(): void
    {
        SiteSetting::current();

        $table = RestaurantTable::query()->create([
            'number' => 12,
            'qr_token' => 'bookmark-table-token-123456789012345678901234567',
            'is_active' => true,
        ]);

        $this->getJson('/api/tables/resolve/'.$table->qr_token)
            ->assertForbidden()
            ->assertJsonPath('scan_session_required', true);
    }
}
