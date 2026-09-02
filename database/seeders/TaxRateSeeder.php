<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::query()->firstOrCreate(
            ['name' => 'ضريبة القيمة المضافة 15%'],
            [
                'rate' => 15,
                'is_inclusive' => false,
                'is_active' => true,
            ],
        );

        TaxRate::query()->firstOrCreate(
            ['name' => 'معفى من الضريبة'],
            [
                'rate' => 0,
                'is_inclusive' => false,
                'is_active' => true,
            ],
        );
    }
}
