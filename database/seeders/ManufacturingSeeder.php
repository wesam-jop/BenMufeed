<?php

namespace Database\Seeders;

use App\Enums\ProductionOrderStatus;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class ManufacturingSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $branch = $this->mainBranch();
        $warehouse = $this->mainWarehouse();
        $user = $this->adminUser();
        $latte = Product::query()->where('sku', 'MENU-LATTE')->first();
        $coffee = Product::query()->where('sku', 'RAW-COFFEE')->first();
        $milk = Product::query()->where('sku', 'RAW-MILK')->first();

        if ($branch === null || $warehouse === null || $user === null || $latte === null) {
            return;
        }

        $order = ProductionOrder::query()->firstOrCreate(
            ['order_number' => 'PROD-2026-001'],
            [
                'branch_id' => $branch->id,
                'product_id' => $latte->id,
                'warehouse_id' => $warehouse->id,
                'planned_qty' => 20,
                'actual_qty' => 20,
                'status' => ProductionOrderStatus::Completed->value,
                'unit_cost' => 6,
                'total_cost' => 120,
                'produced_at' => now()->subDay(),
                'user_id' => $user->id,
            ],
        );

        if ($coffee !== null) {
            ProductionOrderItem::query()->firstOrCreate(
                ['production_order_id' => $order->id, 'product_id' => $coffee->id],
                ['planned_qty' => 0.36, 'actual_qty' => 0.36, 'unit_cost' => 85],
            );
        }

        if ($milk !== null) {
            ProductionOrderItem::query()->firstOrCreate(
                ['production_order_id' => $order->id, 'product_id' => $milk->id],
                ['planned_qty' => 5, 'actual_qty' => 5, 'unit_cost' => 6],
            );
        }
    }
}
