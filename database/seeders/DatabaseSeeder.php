<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            BranchSeeder::class,
            UnitSeeder::class,
            TaxRateSeeder::class,
            ChartOfAccountSeeder::class,
            WarehouseSeeder::class,
            CashRegisterSeeder::class,
            CurrencySeeder::class,
            AdminSeeder::class,
            SiteSettingSeeder::class,
            CmsPageSeeder::class,
        ]);
    }
}
