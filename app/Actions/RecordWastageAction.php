<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Enums\WastageReason;
use App\Models\User;
use App\Models\WastageRecord;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordWastageAction
{
    public function __construct(private StockService $stockService) {}

    /**
     * @param  array{
     *     warehouse_id: int,
     *     product_id: int,
     *     quantity: float|int|string,
     *     reason: WastageReason|string,
     *     notes?: string|null,
     * }  $data
     */
    public function execute(array $data, User $user): WastageRecord
    {
        return DB::transaction(function () use ($data, $user) {
            $quantity = (float) $data['quantity'];
            $reason = $data['reason'] instanceof WastageReason
                ? $data['reason']
                : WastageReason::from($data['reason']);

            $available = (float) ($this->stockService->getBalance(
                (int) $data['warehouse_id'],
                (int) $data['product_id'],
            )?->quantity ?? 0);

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => "الكمية المطلوبة ({$quantity}) أكبر من المتاح في المستودع ({$available}).",
                ]);
            }

            $record = WastageRecord::create([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'quantity' => $quantity,
                'reason' => $reason,
                'notes' => $data['notes'] ?? null,
                'user_id' => $user->id,
            ]);

            $movement = $this->stockService->recordMovement([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'type' => StockMovementType::Wastage,
                'quantity' => $quantity,
                'reason' => $reason->value,
                'notes' => $data['notes'] ?? null,
                'reference' => $record,
                'user_id' => $user->id,
            ]);

            $record->update(['stock_movement_id' => $movement->id]);

            return $record->fresh(['warehouse', 'product.unit', 'user', 'stockMovement']);
        });
    }
}
