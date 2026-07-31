<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function createWithItems(array $orderAttributes, Collection $items): Order
    {
        return DB::transaction(function () use ($orderAttributes, $items) {
            $order = Order::query()->create($orderAttributes);

            foreach ($items as $line) {
                $lineData = is_array($line) ? $line : $line->toArray();
                $addons = $lineData['addons'] ?? [];
                unset($lineData['addons']);

                $orderItem = $order->items()->create($lineData);

                foreach ($addons as $addonRow) {
                    $orderItem->addons()->create($addonRow);
                }
            }

            return $order->load(['items.addons']);
        });
    }
}
