<?php

namespace Tests\Feature;

use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use Tests\TestCase;

class TableQrEntryTest extends TestCase
{
    public function test_table_qr_redirects_to_table_menu(): void
    {
        SiteSetting::current();

        $table = RestaurantTable::query()->create([
            'number' => 3,
            'qr_token' => 'entrytabletoken12345678901234567890123456789012',
            'label' => 'طاولة 3',
            'is_active' => true,
        ]);

        $this->get('/t/'.$table->qr_token)
            ->assertRedirect(route('table.menu'));
    }
}
