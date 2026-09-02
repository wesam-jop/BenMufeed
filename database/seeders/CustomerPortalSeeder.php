<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\LoyaltyTransaction;
use App\Models\Product;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class CustomerPortalSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $customer = Customer::query()->where('phone', '0501111111')->first();
        $latte = Product::query()->where('sku', 'MENU-LATTE')->first();
        $cheesecake = Product::query()->where('sku', 'MENU-CHEESECAKE')->first();

        if ($customer === null) {
            return;
        }

        CustomerAddress::query()->firstOrCreate(
            ['customer_id' => $customer->id, 'label' => 'المنزل'],
            [
                'city' => 'الرياض',
                'district' => 'النرجس',
                'street' => 'شارع 20',
                'building' => '12',
                'floor' => '3',
                'apartment' => '5',
                'notes' => 'بجانب المسجد',
                'is_default' => true,
            ],
        );

        CustomerAddress::query()->firstOrCreate(
            ['customer_id' => $customer->id, 'label' => 'العمل'],
            [
                'city' => 'الرياض',
                'district' => 'العليا',
                'street' => 'طريق الملك فهد',
                'building' => 'برج الأعمال',
                'is_default' => false,
            ],
        );

        if ($latte !== null) {
            $customer->favorites()->syncWithoutDetaching([$latte->id]);
        }

        if ($cheesecake !== null) {
            $customer->favorites()->syncWithoutDetaching([$cheesecake->id]);
        }

        LoyaltyTransaction::query()->firstOrCreate(
            [
                'customer_id' => $customer->id,
                'type' => 'earn',
                'points' => 50,
            ],
            ['balance_after' => 120],
        );
    }
}
