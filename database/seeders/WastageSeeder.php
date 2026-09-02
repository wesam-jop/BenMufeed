<?php

namespace Database\Seeders;

use App\Actions\RecordWastageAction;
use App\Enums\WastageReason;
use App\Models\Product;
use App\Models\WastageRecord;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class WastageSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $warehouse = $this->mainWarehouse();
        $user = $this->adminUser();
        $milk = Product::query()->where('sku', 'RAW-MILK')->first();

        if ($warehouse === null || $user === null || $milk === null) {
            return;
        }

        $exists = WastageRecord::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $milk->id)
            ->where('quantity', 2)
            ->exists();

        if ($exists) {
            return;
        }

        app(RecordWastageAction::class)->execute([
            'warehouse_id' => $warehouse->id,
            'product_id' => $milk->id,
            'quantity' => 2,
            'reason' => WastageReason::Expired,
            'notes' => 'حليب منتهي الصلاحية',
        ], $user);
    }
}
