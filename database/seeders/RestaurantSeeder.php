<?php

namespace Database\Seeders;

use App\Enums\TableStatus;
use App\Models\DeliveryZone;
use App\Models\RestaurantTable;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $branch = $this->mainBranch();

        if ($branch === null) {
            return;
        }

        $tables = [
            ['name' => 'طاولة 1', 'zone' => 'داخلي', 'capacity' => 2],
            ['name' => 'طاولة 2', 'zone' => 'داخلي', 'capacity' => 4],
            ['name' => 'طاولة 3', 'zone' => 'داخلي', 'capacity' => 4],
            ['name' => 'طاولة 4', 'zone' => 'شرفة', 'capacity' => 6],
            ['name' => 'طاولة 5', 'zone' => 'شرفة', 'capacity' => 4],
            ['name' => 'طاولة VIP', 'zone' => 'VIP', 'capacity' => 8],
        ];

        foreach ($tables as $index => $table) {
            RestaurantTable::query()->firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $table['name']],
                [
                    'capacity' => $table['capacity'],
                    'zone' => $table['zone'],
                    'qr_token' => Str::uuid()->toString(),
                    'access_code' => str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'status' => TableStatus::Free->value,
                    'sort_order' => $index + 1,
                ],
            );
        }

        DeliveryZone::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'المنطقة المركزية'],
            ['fee' => 15, 'min_order' => 30, 'is_active' => true],
        );

        DeliveryZone::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'المنطقة الشمالية'],
            ['fee' => 20, 'min_order' => 40, 'is_active' => true],
        );
    }
}
