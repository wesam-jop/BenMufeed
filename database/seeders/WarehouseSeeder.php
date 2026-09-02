<?php

namespace Database\Seeders;

use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'MAIN')->first();

        if ($branch === null) {
            return;
        }

        Warehouse::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'name' => 'المستودع الرئيسي',
            ],
            [
                'type' => WarehouseType::Main,
                'is_active' => true,
            ],
        );

        Warehouse::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'name' => 'مستودع المطبخ',
            ],
            [
                'type' => WarehouseType::Kitchen,
                'is_active' => true,
            ],
        );
    }
}
