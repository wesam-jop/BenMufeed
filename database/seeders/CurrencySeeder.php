<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Services\Shared\CurrencyService;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'SAR',
                'name' => 'ريال سعودي',
                'symbol' => 'ر.س',
                'symbol_position' => 'after',
                'decimal_places' => 2,
                'rate_to_primary' => 1,
                'is_primary' => true,
                'show_on_receipt' => false,
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'code' => 'USD',
                'name' => 'دولار أمريكي',
                'symbol' => '$',
                'symbol_position' => 'before',
                'decimal_places' => 2,
                'rate_to_primary' => 3.75,
                'is_primary' => false,
                'show_on_receipt' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'EUR',
                'name' => 'يورو',
                'symbol' => '€',
                'symbol_position' => 'before',
                'decimal_places' => 2,
                'rate_to_primary' => 4.10,
                'is_primary' => false,
                'show_on_receipt' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($rows as $row) {
            Currency::query()->updateOrCreate(
                ['code' => $row['code']],
                $row,
            );
        }

        app(CurrencyService::class)->forgetCache();
    }
}
