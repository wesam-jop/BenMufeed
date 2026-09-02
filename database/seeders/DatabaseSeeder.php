<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
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
            AdminSeeder::class,
            StaffSeeder::class,
            CatalogSeeder::class,
            InventorySeeder::class,
            SupplierSeeder::class,
            PurchasingSeeder::class,
            RestaurantSeeder::class,
            CustomerSeeder::class,
            CustomerPortalSeeder::class,
            SalesSeeder::class,
            ManufacturingSeeder::class,
            AccountingSeeder::class,
            OperationsSeeder::class,
            WastageSeeder::class,
            SiteSettingSeeder::class,
            CmsPageSeeder::class,
            CurrencySeeder::class,
            ContactMessageSeeder::class,
        ]);
    }
}
