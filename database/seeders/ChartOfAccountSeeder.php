<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string, type: AccountType, parent_code?: string, is_system?: bool}>
     */
    private const ACCOUNTS = [
        ['code' => '1000', 'name' => 'الأصول', 'type' => AccountType::Asset, 'is_system' => true],
        ['code' => '1100', 'name' => 'النقدية', 'type' => AccountType::Asset, 'parent_code' => '1000', 'is_system' => true],
        ['code' => '1110', 'name' => 'البنك', 'type' => AccountType::Asset, 'parent_code' => '1000', 'is_system' => true],
        ['code' => '1200', 'name' => 'المخزون', 'type' => AccountType::Asset, 'parent_code' => '1000', 'is_system' => true],
        ['code' => '1400', 'name' => 'ضريبة المدخلات', 'type' => AccountType::Asset, 'parent_code' => '1000', 'is_system' => true],
        ['code' => '2000', 'name' => 'الخصوم', 'type' => AccountType::Liability, 'is_system' => true],
        ['code' => '2100', 'name' => 'حسابات الموردين', 'type' => AccountType::Liability, 'parent_code' => '2000', 'is_system' => true],
        ['code' => '2200', 'name' => 'ضريبة المخرجات', 'type' => AccountType::Liability, 'parent_code' => '2000', 'is_system' => true],
        ['code' => '3000', 'name' => 'حقوق الملكية', 'type' => AccountType::Equity, 'is_system' => true],
        ['code' => '4000', 'name' => 'الإيرادات', 'type' => AccountType::Revenue, 'is_system' => true],
        ['code' => '4100', 'name' => 'المبيعات', 'type' => AccountType::Revenue, 'parent_code' => '4000', 'is_system' => true],
        ['code' => '5000', 'name' => 'تكلفة البضاعة المباعة', 'type' => AccountType::Expense, 'is_system' => true],
        ['code' => '5100', 'name' => 'تكلفة المبيعات', 'type' => AccountType::Expense, 'parent_code' => '5000', 'is_system' => true],
        ['code' => '6000', 'name' => 'المصروفات', 'type' => AccountType::Expense, 'is_system' => true],
        ['code' => '6100', 'name' => 'مصروفات تشغيلية', 'type' => AccountType::Expense, 'parent_code' => '6000', 'is_system' => true],
    ];

    public function run(): void
    {
        $parents = [];

        foreach (self::ACCOUNTS as $account) {
            if (! isset($account['parent_code'])) {
                $parents[$account['code']] = ChartOfAccount::query()->firstOrCreate(
                    ['code' => $account['code']],
                    [
                        'name' => $account['name'],
                        'type' => $account['type'],
                        'is_system' => $account['is_system'] ?? false,
                        'is_active' => true,
                    ],
                );
            }
        }

        foreach (self::ACCOUNTS as $account) {
            if (! isset($account['parent_code'])) {
                continue;
            }

            ChartOfAccount::query()->firstOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'parent_id' => $parents[$account['parent_code']]->id,
                    'is_system' => $account['is_system'] ?? false,
                    'is_active' => true,
                ],
            );
        }
    }
}
