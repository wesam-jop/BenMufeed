<?php

namespace App\Livewire\Pos;

use App\Support\PosCatalogCache;
use App\Models\Order;
use App\Models\PosSetting;
use App\Models\Product;
use App\Services\PosService;
use App\Services\ProductOptionsService;
use App\Services\RestaurantTableService;
use App\Services\WebsiteOrderWorkflowService;
use App\Support\DispatchesPrintJobs;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
#[Title('انواع البيع — بن مفيد')]
class Cashier extends Component
{
    use DispatchesPrintJobs;

    public string $search = '';

    public ?int $categoryId = null;

    /** @var array<string, array<string, mixed>> */
    public array $cart = [];

    public ?array $customizingProduct = null;

    public ?int $customizerVariantId = null;

    /** @var array<int, int> */
    public array $customizerAddonQty = [];

    public string $customerName = '';

    public string $customerNote = '';

    public ?string $serviceType = null;

    public ?int $tableNumber = null;

    public ?int $editingOrderId = null;

    public ?string $closingPaidAmount = null;

    public ?int $lastOrderId = null;

    public ?string $lastActionMessage = null;

    public int $pendingWebsiteCount = 0;

    public function mount(): void
    {
        $this->refreshWebsiteOrderCount();
    }

    public function refreshWebsiteOrderCount(): void
    {
        $this->pendingWebsiteCount = app(WebsiteOrderWorkflowService::class)->pendingWebsiteOrdersCount();
    }

    public function startTakeaway(): void
    {
        $this->serviceType = 'takeaway';
        $this->tableNumber = null;
        $this->editingOrderId = null;
        $this->closingPaidAmount = null;
        $this->cart = [];
        $this->resetErrorBag();
    }

    public function selectTable(int $number): void
    {
        if (! app(RestaurantTableService::class)->isActiveTableNumber($number)) {
            return;
        }

        $existing = app(PosService::class)->findOpenTableOrder($number);

        if ($existing !== null) {
            $this->loadTableOrder($existing);

            return;
        }

        $this->startTable($number);
    }

    public function startTable(int $number): void
    {
        if (! app(RestaurantTableService::class)->isActiveTableNumber($number)) {
            return;
        }

        $this->serviceType = 'table';
        $this->tableNumber = $number;
        $this->editingOrderId = null;
        $this->closingPaidAmount = null;
        $this->cart = [];
        $this->customerNote = '';
        $this->resetErrorBag();
    }

    public function changeService(): void
    {
        $this->serviceType = null;
        $this->tableNumber = null;
        $this->editingOrderId = null;
        $this->closingPaidAmount = null;
        $this->cart = [];
        $this->customerName = '';
        $this->customerNote = '';
        $this->search = '';
        $this->categoryId = null;
        $this->resetErrorBag();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function addProduct(int $productId): void
    {
        if (! $this->serviceReady) {
            return;
        }

        $product = Product::query()
            ->published()
            ->with(['activeVariants', 'activeAddons'])
            ->withCount(['activeVariants', 'activeAddons'])
            ->find($productId);

        if ($product === null) {
            return;
        }

        if ($product->hasOptions()) {
            $defaultVariant = $product->activeVariants->firstWhere('is_default', true)
                ?? $product->activeVariants->first();

            $this->customizingProduct = [
                'id' => $product->getKey(),
                'name' => $product->name,
                'price' => (float) $product->startingPrice(),
                'variants' => $product->activeVariants->map(fn ($v) => [
                    'id' => $v->getKey(),
                    'name' => $v->name,
                    'price' => (float) $v->price,
                    'is_default' => (bool) $v->is_default,
                ])->values()->all(),
                'addons' => $product->activeAddons->map(fn ($a) => [
                    'id' => $a->getKey(),
                    'name' => $a->name,
                    'price' => (float) $a->price,
                    'max_quantity' => (int) $a->max_quantity,
                ])->values()->all(),
            ];
            $this->customizerVariantId = $defaultVariant?->getKey();
            $this->customizerAddonQty = [];

            return;
        }

        $this->pushCartLine([
            'product_id' => $product->getKey(),
            'product_variant_id' => null,
            'name' => $product->name,
            'price' => (float) $product->price,
            'quantity' => 1,
            'addons' => [],
        ]);

        $this->lastOrderId = null;
        $this->lastActionMessage = null;
    }

    public function cancelCustomizer(): void
    {
        $this->customizingProduct = null;
        $this->customizerVariantId = null;
        $this->customizerAddonQty = [];
    }

    public function selectCustomizerVariant(int $variantId): void
    {
        $this->customizerVariantId = $variantId;
    }

    public function toggleCustomizerAddon(int $addonId): void
    {
        if ($this->customizingProduct === null) {
            return;
        }

        $addon = collect($this->customizingProduct['addons'])->firstWhere('id', $addonId);

        if ($addon === null) {
            return;
        }

        $current = (int) ($this->customizerAddonQty[$addonId] ?? 0);
        $max = (int) ($addon['max_quantity'] ?? 1);

        if ($current >= $max) {
            unset($this->customizerAddonQty[$addonId]);
        } else {
            $this->customizerAddonQty[$addonId] = $current + 1;
        }
    }

    public function confirmCustomizer(): void
    {
        if ($this->customizingProduct === null) {
            return;
        }

        $product = Product::query()
            ->published()
            ->with(['activeVariants', 'activeAddons'])
            ->find($this->customizingProduct['id']);

        if ($product === null) {
            $this->cancelCustomizer();

            return;
        }

        $addonLines = collect($this->customizerAddonQty)
            ->map(fn (int $qty, int $addonId): array => [
                'product_addon_id' => $addonId,
                'quantity' => $qty,
            ])
            ->values()
            ->all();

        try {
            $resolved = app(ProductOptionsService::class)->resolveLine(
                $product,
                $this->customizerVariantId,
                $addonLines,
                1,
            );

            $displayName = app(ProductOptionsService::class)->displayName(
                $resolved['product_name'],
                $resolved['variant_name'],
                $resolved['options_label'],
            );

            $this->pushCartLine([
                'product_id' => $resolved['product_id'],
                'product_variant_id' => $resolved['product_variant_id'],
                'name' => $displayName,
                'price' => $resolved['unit_price'],
                'quantity' => 1,
                'addons' => collect($resolved['addons'])->map(fn (array $addon): array => [
                    'product_addon_id' => $addon['product_addon_id'],
                    'quantity' => $addon['quantity'],
                ])->all(),
            ]);

            $this->cancelCustomizer();
            $this->lastOrderId = null;
            $this->lastActionMessage = null;
        } catch (\Throwable $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function increment(string $lineKey): void
    {
        if (! isset($this->cart[$lineKey])) {
            return;
        }

        $this->cart[$lineKey]['quantity']++;
    }

    public function decrement(string $lineKey): void
    {
        if (! isset($this->cart[$lineKey])) {
            return;
        }

        $this->cart[$lineKey]['quantity']--;

        if ($this->cart[$lineKey]['quantity'] <= 0) {
            unset($this->cart[$lineKey]);
        }
    }

    public function removeItem(string $lineKey): void
    {
        unset($this->cart[$lineKey]);
    }

    public function clearCart(): void
    {
        if ($this->editingOrderId !== null) {
            $order = Order::query()->with('items')->find($this->editingOrderId);

            if ($order !== null) {
                $this->loadTableOrder($order);
            }

            return;
        }

        $this->cart = [];
        $this->customerName = '';
        $this->customerNote = '';
        $this->lastOrderId = null;
        $this->lastActionMessage = null;
    }

    public function checkout(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'السلة فارغة — أضف أطباقاً أولاً.');

            return;
        }

        if ($this->serviceType === 'table') {
            if ($this->editingOrderId !== null) {
                $this->saveTableOrder();

                return;
            }

            $this->sendToTable();

            return;
        }

        try {
            $orderId = app(PosService::class)->placeTakeaway(
                $this->cartLines(),
                auth()->user(),
                filled($this->customerName) ? $this->customerName : null,
                filled($this->customerNote) ? $this->customerNote : null,
            );

            $this->lastOrderId = $orderId;
            $this->lastActionMessage = 'تم البيع السفري — طلب #'.$orderId;
            $this->resetCartState();
            $this->dispatch('pos-sale-complete', orderId: $orderId);

            if (PosSetting::current()->auto_print_cashier) {
                $this->dispatchCashierPrint($orderId);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('cart', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function saveTableOrder(): void
    {
        if ($this->editingOrderId === null || $this->cart === []) {
            $this->addError('cart', 'أضف صنفاً واحداً على الأقل.');

            return;
        }

        try {
            app(PosService::class)->updateOrder(
                $this->editingOrderId,
                $this->cartLines(),
                [
                    'customer_note' => $this->customerNote,
                ],
            );

            $this->lastActionMessage = 'تم تحديث طاولة '.$this->tableNumber.' — فاتورة #'.$this->editingOrderId;

            $order = Order::query()->with('items')->find($this->editingOrderId);

            if ($order !== null) {
                $this->loadTableOrder($order);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('cart', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function closeTable(): void
    {
        if ($this->tableNumber === null || $this->editingOrderId === null) {
            return;
        }

        if ($this->cart === []) {
            $this->addError('close', 'لا يمكن إغلاق طاولة بدون أصناف.');

            return;
        }

        $this->validate([
            'closingPaidAmount' => ['required', 'numeric', 'min:0'],
        ], [
            'closingPaidAmount.required' => 'أدخل المبلغ المقبوض من الزبون.',
        ]);

        try {
            app(PosService::class)->updateOrder(
                $this->editingOrderId,
                $this->cartLines(),
                [
                    'customer_note' => $this->customerNote,
                ],
            );

            $closedOrderId = $this->editingOrderId;

            app(PosService::class)->closeTable(
                $this->tableNumber,
                (float) $this->closingPaidAmount,
                auth()->user(),
            );

            $this->lastOrderId = $closedOrderId;
            $this->lastActionMessage = 'تم إغلاق طاولة '.$this->tableNumber.' — مقبوض '.number_format((float) $this->closingPaidAmount, 2);
            $this->resetCartState();

            if (PosSetting::current()->auto_print_cashier && $closedOrderId !== null) {
                $this->dispatchCashierPrint($closedOrderId);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('close', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('close', $e->getMessage());
        }
    }

    public function sendToTable(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'السلة فارغة — أضف أطباقاً أولاً.');

            return;
        }

        if ($this->tableNumber === null || ! app(RestaurantTableService::class)->isActiveTableNumber($this->tableNumber)) {
            $this->addError('cart', 'اختر طاولة من القائمة.');

            return;
        }

        try {
            $orderId = app(PosService::class)->sendToTable(
                $this->cartLines(),
                auth()->user(),
                $this->tableNumber,
                filled($this->customerNote) ? $this->customerNote : null,
            );

            $this->lastOrderId = $orderId;
            $this->lastActionMessage = 'تم إرسال الطلب — طاولة '.$this->tableNumber.' (# '.$orderId.')';
            $this->resetCartState();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('cart', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('cart', $e->getMessage());
        }
    }

    public function getServiceReadyProperty(): bool
    {
        if ($this->serviceType === 'takeaway') {
            return true;
        }

        return $this->serviceType === 'table' && $this->tableNumber !== null;
    }

    public function getIsEditingTableProperty(): bool
    {
        return $this->editingOrderId !== null && $this->serviceType === 'table';
    }

    public function getCartTotalProperty(): float
    {
        return round(collect($this->cart)->sum(fn (array $row) => $row['price'] * $row['quantity']), 2);
    }

    public function getCartCountProperty(): int
    {
        return (int) collect($this->cart)->sum('quantity');
    }

    public function render()
    {
        $categories = PosCatalogCache::categories();

        $pos = app(PosService::class);
        $tables = app(RestaurantTableService::class)->activeTables();
        $occupiedTables = $pos->occupiedTableNumbers();

        return view('livewire.pos.cashier', [
            'categories' => $categories,
            'products' => $this->loadProducts($categories),
            'currency' => config('store.currency_symbol', '$'),
            'openTablesCount' => count($occupiedTables),
            'occupiedTables' => $occupiedTables,
            'restaurantTables' => $tables,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cartLines(): array
    {
        return collect($this->cart)->map(fn (array $row): array => [
            'product_id' => (int) $row['product_id'],
            'product_variant_id' => $row['product_variant_id'] ?? null,
            'quantity' => (int) $row['quantity'],
            'addons' => collect($row['addons'] ?? [])->map(fn (array $addon): array => [
                'product_addon_id' => (int) $addon['product_addon_id'],
                'quantity' => max(1, (int) ($addon['quantity'] ?? 1)),
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function pushCartLine(array $line): void
    {
        $lineKey = $this->buildLineKey(
            (int) $line['product_id'],
            $line['product_variant_id'] ?? null,
            $line['addons'] ?? [],
        );

        if (isset($this->cart[$lineKey])) {
            $this->cart[$lineKey]['quantity']++;
        } else {
            $this->cart[$lineKey] = [
                'line_key' => $lineKey,
                'product_id' => (int) $line['product_id'],
                'product_variant_id' => $line['product_variant_id'] ?? null,
                'name' => (string) $line['name'],
                'price' => (float) $line['price'],
                'quantity' => (int) ($line['quantity'] ?? 1),
                'addons' => $line['addons'] ?? [],
            ];
        }
    }

    /**
     * @param  array<int, array{product_addon_id: int, quantity?: int}>  $addons
     */
    private function buildLineKey(int $productId, ?int $variantId, array $addons): string
    {
        $addonPart = collect($addons)
            ->map(fn (array $addon): string => $addon['product_addon_id'].':'.max(1, (int) ($addon['quantity'] ?? 1)))
            ->sort()
            ->implode(',');

        return $productId.':'.($variantId ?? 0).':'.$addonPart;
    }

    private function loadTableOrder(Order $order): void
    {
        $this->serviceType = 'table';
        $this->tableNumber = $order->table_number;
        $this->editingOrderId = $order->getKey();
        $this->cart = [];

        $order->loadMissing(['items.addons']);

        foreach ($order->items as $item) {
            $lineKey = 'order-item-'.$item->getKey();
            $this->cart[$lineKey] = [
                'line_key' => $lineKey,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'name' => $item->displayLabel(),
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'addons' => $item->addons->map(fn ($addon) => [
                    'product_addon_id' => $addon->product_addon_id,
                    'quantity' => (int) $addon->quantity,
                ])->all(),
            ];
        }

        $this->customerNote = $order->customer_note ?? '';
        $this->closingPaidAmount = (string) round((float) $order->total, 2);
        $this->resetErrorBag();
    }

    private function resetCartState(): void
    {
        $this->cart = [];
        $this->customerName = '';
        $this->customerNote = '';
        $this->search = '';
        $this->categoryId = null;
        $this->serviceType = null;
        $this->tableNumber = null;
        $this->editingOrderId = null;
        $this->closingPaidAmount = null;
        $this->resetErrorBag();
    }

    private function loadProducts(Collection $categories): Collection
    {
        if ($this->categoryId === null && trim($this->search) === '') {
            return PosCatalogCache::defaultProducts();
        }

        $query = Product::query()
            ->published()
            ->with(['category:id,name'])
            ->withCount(['activeVariants', 'activeAddons'])
            ->orderBy('name');

        if ($this->categoryId !== null) {
            $query->where('category_id', $this->categoryId);
        }

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        return $query->limit(120)->get();
    }
}
