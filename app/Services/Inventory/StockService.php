<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * @param  array{
     *     warehouse_id: int,
     *     product_id: int,
     *     type: StockMovementType|string,
     *     quantity: float|int|string,
     *     unit_cost?: float|int|string,
     *     batch_id?: int|null,
     *     reference?: Model|null,
     *     notes?: string|null,
     *     reason?: string|null,
     *     user_id?: int|null,
     *     branch_id?: int|null,
     * }  $data
     */
    public function recordMovement(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::query()->lockForUpdate()->findOrFail($data['warehouse_id']);
            $product = Product::query()->findOrFail($data['product_id']);

            $type = $data['type'] instanceof StockMovementType
                ? $data['type']
                : StockMovementType::from($data['type']);

            $quantity = (float) $data['quantity'];

            if ($quantity <= 0 && $type !== StockMovementType::Adjustment) {
                throw new RuntimeException('Movement quantity must be greater than zero.');
            }

            $unitCost = (float) ($data['unit_cost'] ?? $product->cost_price ?? 0);
            $quantityBefore = $this->getBalance($warehouse->id, $product->id)?->quantity ?? 0.0;
            $quantityAfter = $this->calculateQuantityAfter($type, $quantityBefore, $quantity);

            if ($quantityAfter < 0 && ! $product->allow_negative_stock) {
                throw new RuntimeException("المخزون غير كافٍ للمنتج [{$product->name}].");
            }

            $movement = StockMovement::create([
                'branch_id' => $data['branch_id'] ?? $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'batch_id' => $data['batch_id'] ?? null,
                'type' => $type->value,
                'quantity' => abs($quantity),
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'unit_cost' => $unitCost,
                'total_cost' => abs($quantity) * $unitCost,
                'reference_type' => isset($data['reference']) ? $data['reference']->getMorphClass() : null,
                'reference_id' => $data['reference']?->getKey(),
                'notes' => $data['notes'] ?? null,
                'reason' => $data['reason'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            $this->updateBalanceCache($warehouse->id, $product->id, $type, abs($quantity), $unitCost);

            return $movement;
        });
    }

    public function getBalance(int $warehouseId, int $productId): ?StockBalance
    {
        return StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();
    }

    /**
     * @return list<StockMovement>
     */
    public function consumeFromRecipe(
        Product $product,
        float $quantity,
        int $warehouseId,
        ?int $variantId = null,
        ?Model $reference = null,
        ?User $user = null,
    ): array {
        return DB::transaction(function () use ($product, $quantity, $warehouseId, $variantId, $reference, $user) {
            if (! $product->track_stock) {
                return [];
            }

            $recipe = Recipe::query()
                ->where('product_id', $product->id)
                ->when($variantId, fn ($query) => $query->where('variant_id', $variantId))
                ->where('is_active', true)
                ->with('items.ingredient')
                ->first();

            if ($recipe === null) {
                return [
                    $this->recordMovement([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                        'type' => StockMovementType::Out,
                        'quantity' => $quantity,
                        'reference' => $reference,
                        'user_id' => $user?->id,
                    ]),
                ];
            }

            $scale = $quantity / max((float) $recipe->yield_quantity, 1);
            $movements = [];

            foreach ($recipe->items as $item) {
                $ingredientQty = (float) $item->quantity * $scale;
                $wasteMultiplier = 1 + ((float) $item->waste_percent / 100);
                $requiredQty = $ingredientQty * $wasteMultiplier;

                $movements[] = $this->recordMovement([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item->ingredient_product_id,
                    'type' => StockMovementType::Out,
                    'quantity' => $requiredQty,
                    'reference' => $reference,
                    'user_id' => $user?->id,
                    'notes' => "Recipe consumption for {$product->name}",
                ]);
            }

            return $movements;
        });
    }

    public function receiveStock(
        int $warehouseId,
        int $productId,
        float $quantity,
        float $unitCost,
        ?Model $reference = null,
        ?User $user = null,
        ?string $notes = null,
    ): StockMovement {
        return $this->recordMovement([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'type' => StockMovementType::In,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference' => $reference,
            'user_id' => $user?->id,
            'notes' => $notes,
        ]);
    }

    public function transfer(StockTransfer $transfer, ?User $user = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            if ($transfer->status !== 'draft') {
                throw new RuntimeException('Only draft transfers can be executed.');
            }

            $transfer->loadMissing('items.product');

            foreach ($transfer->items as $item) {
                /** @var StockTransferItem $item */
                $this->recordMovement([
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => StockMovementType::TransferOut,
                    'quantity' => (float) $item->quantity,
                    'reference' => $transfer,
                    'user_id' => $user?->id ?? $transfer->user_id,
                ]);

                $this->recordMovement([
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => StockMovementType::TransferIn,
                    'quantity' => (float) $item->quantity,
                    'reference' => $transfer,
                    'user_id' => $user?->id ?? $transfer->user_id,
                ]);
            }

            $transfer->update([
                'status' => 'completed',
                'approved_by' => $user?->id ?? $transfer->approved_by,
            ]);

            return $transfer->fresh(['items']);
        });
    }

    public function adjustStock(
        int $warehouseId,
        int $productId,
        float $newQuantity,
        ?string $reason = null,
        ?Model $reference = null,
        ?User $user = null,
    ): StockMovement {
        $currentQty = $this->getBalance($warehouseId, $productId)?->quantity ?? 0.0;
        $difference = $newQuantity - $currentQty;

        if ($difference == 0.0) {
            throw new RuntimeException('No stock adjustment needed; quantities match.');
        }

        return $this->recordMovement([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'type' => StockMovementType::Adjustment,
            'quantity' => $difference,
            'reference' => $reference,
            'reason' => $reason,
            'user_id' => $user?->id,
        ]);
    }

    public function updateBalanceCache(
        int $warehouseId,
        int $productId,
        ?StockMovementType $type = null,
        ?float $quantity = null,
        ?float $unitCost = null,
    ): StockBalance {
        return DB::transaction(function () use ($warehouseId, $productId, $type, $quantity, $unitCost) {
            $balance = StockBalance::query()
                ->lockForUpdate()
                ->firstOrNew([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                ]);

            if ($type === null || $quantity === null) {
                $latestMovement = StockMovement::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->latest('id')
                    ->first();

                $balance->quantity = $latestMovement?->quantity_after ?? 0;

                return tap($balance)->save();
            }

            $currentQty = (float) ($balance->quantity ?? 0);
            $currentAvgCost = (float) ($balance->avg_cost ?? 0);

            $balance->quantity = $this->calculateQuantityAfter($type, $currentQty, $quantity);

            if ($this->isInbound($type) && $unitCost !== null) {
                $inboundQty = abs($quantity);
                $totalValue = ($currentQty * $currentAvgCost) + ($inboundQty * $unitCost);
                $totalQty = $currentQty + $inboundQty;

                $balance->avg_cost = $totalQty > 0 ? $totalValue / $totalQty : $unitCost;
            } elseif ($this->isOutbound($type)) {
                $balance->avg_cost = $currentAvgCost;
            } elseif ($type === StockMovementType::Adjustment) {
                $balance->avg_cost = $currentAvgCost;
            }

            $balance->save();

            return $balance;
        });
    }

    private function calculateQuantityAfter(StockMovementType $type, float $before, float $quantity): float
    {
        if ($type === StockMovementType::Adjustment) {
            return $before + $quantity;
        }

        if ($this->isInbound($type)) {
            return $before + abs($quantity);
        }

        return $before - abs($quantity);
    }

    private function isInbound(StockMovementType $type): bool
    {
        return in_array($type, [
            StockMovementType::In,
            StockMovementType::TransferIn,
            StockMovementType::ProductionIn,
            StockMovementType::ReturnIn,
            StockMovementType::OpeningBalance,
        ], true);
    }

    private function isOutbound(StockMovementType $type): bool
    {
        return in_array($type, [
            StockMovementType::Out,
            StockMovementType::TransferOut,
            StockMovementType::ProductionOut,
            StockMovementType::Wastage,
            StockMovementType::ReturnOut,
        ], true);
    }
}
