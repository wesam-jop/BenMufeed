<div class="pos-shell">
    <x-pos.header :open-tables-count="$openTablesCount" />

    @error('edit')
        <div class="pos-toast pos-toast-error">{{ $message }}</div>
    @enderror

    <div class="pos-main">
        <section class="pos-products-panel">
            <div class="pos-page-head px-1 pb-3">
                <div>
                    <a href="{{ route('pos.invoices') }}" class="pos-back-link inline-flex items-center gap-1">
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                        سجل الفواتير
                    </a>
                    <h1 class="pos-page-title">تعديل فاتورة #{{ $editingOrderId }}</h1>
                    @if ($editingOrder)
                        <p class="pos-page-sub">
                            {{ $editingOrder->service_type?->label() ?? '—' }}
                            @if ($editingOrder->table_number) · طاولة {{ $editingOrder->table_number }} @endif
                            · {{ $editingOrder->status->label() }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="pos-search-wrap mb-3 px-1">
                <input type="search" wire:model.live.debounce.300ms="search" class="pos-search" placeholder="ابحث عن طبق لإضافته...">
            </div>

            <div class="pos-categories px-1">
                <button type="button" wire:click="selectCategory(null)" @class(['pos-cat-btn', 'pos-cat-btn-active' => $categoryId === null])>الكل</button>
                @foreach ($categories as $category)
                    <button type="button" wire:click="selectCategory({{ $category->id }})" @class(['pos-cat-btn', 'pos-cat-btn-active' => $categoryId === $category->id])>{{ $category->name }}</button>
                @endforeach
            </div>

            <x-pos.product-grid
                :products="$products"
                :currency="$currency"
                :show-category="$categoryId === null"
            />
        </section>

        <aside class="pos-cart-panel">
            <div class="pos-cart-header">
                <h2 class="pos-cart-title">بنود الفاتورة</h2>
            </div>

            <div class="pos-cart-items">
                @forelse ($editCart as $productId => $item)
                    <div class="pos-cart-item" wire:key="edit-{{ $productId }}">
                        <div class="pos-cart-item-info">
                            <p class="pos-cart-item-name">{{ $item['name'] }}</p>
                            <p class="pos-cart-item-price">{{ number_format($item['price'], 2) }} {{ $currency }}</p>
                        </div>
                        <div class="pos-qty-controls">
                            <button type="button" wire:click="editDecrement({{ $productId }})" class="pos-qty-btn">−</button>
                            <span class="pos-qty">{{ $item['quantity'] }}</span>
                            <button type="button" wire:click="editIncrement({{ $productId }})" class="pos-qty-btn">+</button>
                        </div>
                        <p class="pos-cart-line-total">{{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                        <button type="button" wire:click="editRemoveItem({{ $productId }})" class="pos-remove-btn">×</button>
                    </div>
                @empty
                    <div class="pos-cart-empty"><p>أضف أصنافاً من القائمة</p></div>
                @endforelse
            </div>

            <div class="pos-cart-footer">
                <input type="text" wire:model="editCustomerName" class="pos-input" placeholder="اسم العميل">
                <input type="text" wire:model="editCustomerNote" class="pos-input" placeholder="ملاحظة">
                @if ($editingOrder && $editingOrder->status === \App\Enums\OrderStatus::Completed)
                    <label class="pos-close-label">المبلغ المقبوض</label>
                    <input type="number" step="0.01" min="0" wire:model="editPaidAmount" class="pos-input">
                @endif
                <div class="pos-total-row">
                    <span>الإجمالي</span>
                    <strong>{{ number_format($this->editCartTotal, 2) }} {{ $currency }}</strong>
                </div>
                <button type="button" wire:click="saveEditOrder" wire:loading.attr="disabled" class="pos-checkout-btn">حفظ التعديلات</button>
                <a href="{{ route('pos.invoices') }}" class="pos-clear-btn text-center">إلغاء</a>
            </div>
        </aside>
    </div>
</div>
