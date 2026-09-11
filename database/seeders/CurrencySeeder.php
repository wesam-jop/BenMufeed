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
                'code' => 'USD',
                'name' => 'دولار أمريكي',
                'symbol' => '$',
                'symbol_position' => 'before',
                'decimal_places' => 2,
                'rate_to_primary' => 1,
                'is_primary' => true,
                'show_on_receipt' => false,
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'code' => 'TRY',
                'name' => 'ليرة تركية',
                'symbol' => '₺',
                'symbol_position' => 'after',
                'decimal_places' => 2,
                // 1 USD = 34.5 TRY  → عدّل من صفحة التصريف
                'rate_to_primary' => 34.5,
                'is_primary' => false,
                'show_on_receipt' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'SYP',
                'name' => 'ليرة سورية',
                'symbol' => 'ل.س',
                'symbol_position' => 'after',
                'decimal_places' => 0,
                // 1 USD = 12,500 SYP  → عدّل من صفحة التصريف
                'rate_to_primary' => 12500,
                'is_primary' => false,
                'show_on_receipt' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            // عملات قديمة — تبقى معطّلة إن وُجدت
            [
                'code' => 'SAR',
                'name' => 'ريال سعودي',
                'symbol' => 'ر.س',
                'symbol_position' => 'after',
                'decimal_places' => 2,
                'rate_to_primary' => 3.75,
                'is_primary' => false,
                'show_on_receipt' => false,
                'is_active' => false,
                'sort_order' => 99,
            ],
            [
                'code' => 'EUR',
                'name' => 'يورو',
                'symbol' => '€',
                'symbol_position' => 'before',
                'decimal_places' => 2,
                'rate_to_primary' => 0.92,
                'is_primary' => false,
                'show_on_receipt' => false,
                'is_active' => false,
                'sort_order' => 100,
            ],
        ];

        // ألغِ الرئيسية القديمة قبل إعادة التعيين
        Currency::query()->update(['is_primary' => false]);

        foreach ($rows as $row) {
            Currency::query()->updateOrCreate(
                ['code' => $row['code']],
                $row,
            );
        }

        $service = app(CurrencyService::class);
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $service->makePrimary($usd);
        $service->forgetCache();
    }
}
