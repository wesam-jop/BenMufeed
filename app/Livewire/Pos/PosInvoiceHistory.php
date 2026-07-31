<?php

namespace App\Livewire\Pos;

use App\Enums\OrderSource;
use App\Support\PosCatalogCache;
use App\Models\Order;
use App\Models\Product;
use App\Services\PosService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
class PosInvoiceHistory extends Component
{
    public string $historyDate = '';

    public string $search = '';

    public ?int $categoryId = null;

    public ?int $editingOrderId = null;

    /** @var array<int, array{product_id: int, name: string, price: float, quantity: int}> */
    public array $editCart = [];

    public string $editCustomerName = '';

    public string $editCustomerNote = '';

    public ?string $editPaidAmount = null;

    public ?string $successMessage = null;

    public function mount(?Order $order = null): void
    {
        $this->historyDate = now()->format('Y-m-d');

        if ($order !== null) {
            abort_unless($order->source === OrderSource::Pos, 404);
            $this->loadOrderForEdit($order->getKey());
        }
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function addProduct(int $productId): void
    {
        if ($this->editingOrderId === null) {
            return;
        }

        $product = Product::query()->published()->find($productId);

        if ($product === null) {
            return;
        }

        if (isset($this->editCart[$productId])) {
            $this->editCart[$productId]['quantity']++;
        } else {
            $this->editCart[$productId] = [
                'product_id' => $product->getKey(),
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
            ];
        }
    }

    public function editIncrement(int $productId): void
    {
        if (isset($this->editCart[$productId])) {
            $this->editCart[$productId]['quantity']++;
        }
    }

    public function editDecrement(int $productId): void
    {
        if (! isset($this->editCart[$productId])) {
            return;
        }

        $this->editCart[$productId]['quantity']--;

        if ($this->editCart[$productId]['quantity'] <= 0) {
            unset($this->editCart[$productId]);
        }
    }

    public function editRemoveItem(int $productId): void
    {
        unset($this->editCart[$productId]);
    }

    public function saveEditOrder(): void
    {
        if ($this->editingOrderId === null || $this->editCart === []) {
            $this->addError('edit', 'أضف صنفاً واحداً على الأقل.');

            return;
        }

        try {
            app(PosService::class)->updateOrder(
                $this->editingOrderId,
                collect($this->editCart)->map(fn (array $row) => [
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                ])->values()->all(),
                [
                    'customer_name' => filled($this->editCustomerName) ? $this->editCustomerName : null,
                    'customer_note' => $this->editCustomerNote,
                    'paid_amount' => filled($this->editPaidAmount) ? (float) $this->editPaidAmount : null,
                ],
            );

            $this->successMessage = 'تم تحديث الفاتورة #'.$this->editingOrderId;
            $this->redirect(route('pos.invoices'), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('edit', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('edit', $e->getMessage());
        }
    }

    public function deleteOrder(int $orderId): void
    {
        try {
            app(PosService::class)->deleteOrder($orderId);
            $this->successMessage = 'تم حذف الفاتورة #'.$orderId;

            if ($this->editingOrderId === $orderId) {
                $this->redirect(route('pos.invoices'), navigate: true);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('edit', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('edit', $e->getMessage());
        }
    }

    public function getEditCartTotalProperty(): float
    {
        return round(collect($this->editCart)->sum(fn (array $row) => $row['price'] * $row['quantity']), 2);
    }

    public function render()
    {
        $pos = app(PosService::class);
        $openTablesCount = $pos->openTablesCount();

        if ($this->editingOrderId) {
            $categories = PosCatalogCache::categories();

            return view('livewire.pos.invoice-history-edit', [
                'currency' => config('store.currency_symbol', '$'),
                'openTablesCount' => $openTablesCount,
                'editingOrder' => Order::query()->find($this->editingOrderId),
                'categories' => $categories,
                'products' => $this->loadProducts($categories),
            ])->title('تعديل فاتورة #'.$this->editingOrderId);
        }

        return view('livewire.pos.invoice-history', [
            'currency' => config('store.currency_symbol', '$'),
            'openTablesCount' => $openTablesCount,
            'historyOrders' => $pos->recentOrders(filled($this->historyDate) ? $this->historyDate : null),
        ])->title('سجل الفواتير — نقطة البيع');
    }

    protected function loadOrderForEdit(int $orderId): void
    {
        $order = Order::query()
            ->with('items')
            ->where('source', OrderSource::Pos)
            ->findOrFail($orderId);

        $this->editingOrderId = $order->getKey();
        $this->editCart = [];

        foreach ($order->items as $item) {
            $this->editCart[$item->product_id] = [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
            ];
        }

        $this->editCustomerName = $order->customer_name ?? '';
        $this->editCustomerNote = $order->customer_note ?? '';
        $this->editPaidAmount = $order->paid_amount !== null ? (string) $order->paid_amount : (string) $order->total;
    }

    private function loadProducts(Collection $categories): Collection
    {
        $query = Product::query()
            ->published()
            ->with(['category:id,name'])
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
