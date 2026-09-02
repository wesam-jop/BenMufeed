<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\TableSession;
use App\Models\Warehouse;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $branch = $this->mainBranch();
        $mainWarehouse = $this->mainWarehouse();
        $user = $this->adminUser();
        $kitchenWarehouse = Warehouse::query()
            ->where('branch_id', $branch?->id)
            ->where('name', 'مستودع المطبخ')
            ->first();
        $table = RestaurantTable::query()->where('name', 'طاولة 2')->first();
        $coffee = Product::query()->where('sku', 'RAW-COFFEE')->first();

        if ($branch === null || $mainWarehouse === null || $user === null) {
            return;
        }

        if ($table !== null) {
            TableSession::query()->firstOrCreate(
                ['table_id' => $table->id, 'status' => 'open'],
                [
                    'guest_count' => 3,
                    'opened_at' => now()->subMinutes(45),
                ],
            );
        }

        if ($kitchenWarehouse !== null && $coffee !== null) {
            $transfer = StockTransfer::query()->firstOrCreate(
                [
                    'from_warehouse_id' => $mainWarehouse->id,
                    'to_warehouse_id' => $kitchenWarehouse->id,
                    'status' => 'completed',
                ],
                [
                    'user_id' => $user->id,
                    'approved_by' => $user->id,
                ],
            );

            StockTransferItem::query()->firstOrCreate(
                ['stock_transfer_id' => $transfer->id, 'product_id' => $coffee->id],
                ['quantity' => 5],
            );
        }

        if ($coffee !== null) {
            $count = StockCount::query()->firstOrCreate(
                ['warehouse_id' => $mainWarehouse->id, 'status' => 'approved'],
                [
                    'counted_at' => now()->subDays(3),
                    'approved_by' => $user->id,
                ],
            );

            StockCountItem::query()->firstOrCreate(
                ['stock_count_id' => $count->id, 'product_id' => $coffee->id],
                [
                    'system_qty' => 25,
                    'actual_qty' => 24.5,
                    'difference' => -0.5,
                ],
            );
        }
    }
}
