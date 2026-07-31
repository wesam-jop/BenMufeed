<?php

namespace Tests\Feature;

use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableQrExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_single_table_qr_png(): void
    {
        $admin = User::factory()->admin()->create();
        $table = RestaurantTable::query()->create([
            'number' => 7,
            'qr_token' => RestaurantTable::generateToken(),
            'label' => 'طاولة تجريبية',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tables.qr.png', $table));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent() ?: '');
    }

    public function test_admin_can_download_all_table_qr_zip(): void
    {
        $admin = User::factory()->admin()->create();
        foreach ([1, 2] as $number) {
            RestaurantTable::query()->create([
                'number' => $number,
                'qr_token' => RestaurantTable::generateToken(),
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tables.qr.all'));

        $response->assertOk();
        $response->assertDownload('tables-qr.zip');
    }
}
