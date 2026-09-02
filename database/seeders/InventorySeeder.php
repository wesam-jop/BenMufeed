<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    use InteractsWithMainBranch;

    /**
     * @var list<array{sku: string, qty: float, cost: float}>
     */
    private const OPENING_STOCK = [
        ['sku' => 'RAW-COFFEE', 'qty' => 25, 'cost' => 85],
        ['sku' => 'RAW-MILK', 'qty' => 120, 'cost' => 6],
        ['sku' => 'RAW-SUGAR', 'qty' => 40, 'cost' => 4],
        ['sku' => 'RAW-CREAM', 'qty' => 30, 'cost' => 18],
        ['sku' => 'RAW-CHOCOLATE', 'qty' => 15, 'cost' => 45],
        ['sku' => 'RAW-FLOUR', 'qty' => 50, 'cost' => 3.5],
    ];

    public function run(): void
    {
        $branch = $this->mainBranch();
        $warehouse = $this->mainWarehouse();
        $user = $this->adminUser();

        if ($branch === null || $warehouse === null || $user === null) {
            return;
        }

        foreach (self::OPENING_STOCK as $item) {
            $product = Product::query()->where('sku', $item['sku'])->first();

            if ($product === null) {
                continue;
            }

            StockBalance::query()->updateOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                ['quantity' => $item['qty'], 'avg_cost' => $item['cost'], 'updated_at' => now()],
            );

            $batch = StockBatch::query()->firstOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'batch_number' => 'OPEN-'.str_replace('RAW-', '', $item['sku']),
                ],
                [
                    'quantity_remaining' => $item['qty'],
                    'unit_cost' => $item['cost'],
                ],
            );

            StockMovement::query()->firstOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'type' => StockMovementType::OpeningBalance->value,
                    'reference_type' => StockBatch::class,
                    'reference_id' => $batch->id,
                ],
                [
                    'branch_id' => $branch->id,
                    'batch_id' => $batch->id,
                    'quantity' => $item['qty'],
                    'quantity_before' => 0,
                    'quantity_after' => $item['qty'],
                    'unit_cost' => $item['cost'],
                    'total_cost' => $item['qty'] * $item['cost'],
                    'notes' => 'رصيد افتتاحي',
                    'user_id' => $user->id,
                ],
            );
        }
    }
}
