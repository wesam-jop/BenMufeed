<?php

namespace App\Services\Storefront;

use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'storefront_cart';

    public function __construct(
        protected Session $session,
        protected StorefrontService $storefront,
    ) {}

    /**
     * @return array{
     *     items: list<array{product_id: int, name: string, price: float, image: ?string, quantity: float}>,
     *     location_lat: ?float,
     *     location_lng: ?float,
     *     location_address: ?string,
     *     location_notes: ?string,
     *     count: int,
     *     subtotal: float
     * }
     */
    public function summary(): array
    {
        $cart = $this->get();
        $items = $cart['items'];
        $count = (int) array_sum(array_column($items, 'quantity'));
        $subtotal = array_sum(array_map(
            fn (array $item) => $item['price'] * $item['quantity'],
            $items,
        ));

        return [
            'items' => $items,
            'location_lat' => $cart['location_lat'],
            'location_lng' => $cart['location_lng'],
            'location_address' => $cart['location_address'],
            'location_notes' => $cart['location_notes'],
            'count' => $count,
            'subtotal' => round($subtotal, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $cart = $this->session->get(self::SESSION_KEY, []);

        return [
            'items' => $cart['items'] ?? [],
            'location_lat' => $cart['location_lat'] ?? null,
            'location_lng' => $cart['location_lng'] ?? null,
            'location_address' => $cart['location_address'] ?? null,
            'location_notes' => $cart['location_notes'] ?? null,
        ];
    }

    public function add(int $productId, float $quantity = 1): array
    {
        $product = $this->findSellableProduct($productId);
        $cart = $this->get();
        $items = $cart['items'];
        $found = false;

        foreach ($items as &$item) {
            if ($item['product_id'] === $product->id) {
                $item['quantity'] = round($item['quantity'] + $quantity, 4);
                $found = true;
                break;
            }
        }

        unset($item);

        if (! $found) {
            $items[] = $this->formatItem($product, $quantity);
        }

        return $this->saveItems($items);
    }

    public function update(int $productId, float $quantity): array
    {
        if ($quantity <= 0) {
            return $this->remove($productId);
        }

        $this->findSellableProduct($productId);
        $cart = $this->get();
        $items = $cart['items'];
        $updated = false;

        foreach ($items as &$item) {
            if ($item['product_id'] === $productId) {
                $item['quantity'] = round($quantity, 4);
                $updated = true;
                break;
            }
        }

        unset($item);

        if (! $updated) {
            throw ValidationException::withMessages([
                'product_id' => ['المنتج غير موجود في السلة.'],
            ]);
        }

        return $this->saveItems($items);
    }

    public function remove(int $productId): array
    {
        $cart = $this->get();
        $items = array_values(array_filter(
            $cart['items'],
            fn (array $item) => $item['product_id'] !== $productId,
        ));

        return $this->saveItems($items);
    }

    /**
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    public function updateLocation(array $location): array
    {
        $cart = $this->get();
        $cart['location_lat'] = isset($location['location_lat']) ? (float) $location['location_lat'] : null;
        $cart['location_lng'] = isset($location['location_lng']) ? (float) $location['location_lng'] : null;
        $cart['location_address'] = $location['location_address'] ?? null;
        $cart['location_notes'] = $location['location_notes'] ?? null;

        $this->session->put(self::SESSION_KEY, $cart);

        return $this->summary();
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * @return list<array{product_id: int, quantity: float}>
     */
    public function lineItems(): array
    {
        return array_map(
            fn (array $item) => [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ],
            $this->get()['items'],
        );
    }

    public function deliveryAddressText(): ?string
    {
        $cart = $this->get();
        $parts = array_filter([
            $cart['location_address'] ? 'العنوان: '.$cart['location_address'] : null,
            ($cart['location_lat'] && $cart['location_lng'])
                ? 'الإحداثيات: '.$cart['location_lat'].', '.$cart['location_lng']
                : null,
            $cart['location_notes'] ? 'ملاحظات الموقع: '.$cart['location_notes'] : null,
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }

    protected function findSellableProduct(int $productId): Product
    {
        $branchId = $this->storefront->defaultBranch()?->id;

        $product = $this->storefront->productsQuery($branchId)
            ->whereKey($productId)
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => ['المنتج غير متاح.'],
            ]);
        }

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    protected function saveItems(array $items): array
    {
        $cart = $this->get();
        $cart['items'] = array_values($items);
        $this->session->put(self::SESSION_KEY, $cart);

        return $this->summary();
    }

    /**
     * @return array{product_id: int, name: string, price: float, image: ?string, quantity: float}
     */
    protected function formatItem(Product $product, float $quantity): array
    {
        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->sale_price,
            'image' => $product->image_url,
            'quantity' => round($quantity, 4),
        ];
    }
}
