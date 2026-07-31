@props([
    'products',
    'currency',
    'showCategory' => false,
])

<div
    class="pos-catalog"
    x-data="{
        layout: localStorage.getItem('posProductLayout') || 'grid',
        setLayout(mode) {
            this.layout = mode;
            localStorage.setItem('posProductLayout', mode);
        },
    }"
>
    <div class="pos-catalog-meta">
        <span class="pos-catalog-count">{{ $products->count() }} منتج</span>
        <div class="pos-view-toggle" role="group" aria-label="طريقة العرض">
            <button
                type="button"
                class="pos-view-btn"
                :class="{ 'pos-view-btn-active': layout === 'grid' }"
                @click="setLayout('grid')"
                title="عرض شبكة"
            >
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                <span>شبكة</span>
            </button>
            <button
                type="button"
                class="pos-view-btn"
                :class="{ 'pos-view-btn-active': layout === 'list' }"
                @click="setLayout('list')"
                title="عرض قائمة"
            >
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
                <span>قائمة</span>
            </button>
        </div>
    </div>

    <div
        class="pos-catalog-grid"
        :class="layout === 'list' ? 'pos-catalog-grid--list' : 'pos-catalog-grid--grid'"
        wire:loading.class="pos-loading"
    >
        @forelse ($products as $product)
            @php
                $hasOptions = (($product->active_variants_count ?? 0) + ($product->active_addons_count ?? 0)) > 0;
                $priceLabel = ($product->active_variants_count ?? 0) > 0
                    ? 'من '.number_format($product->startingPrice(), 2)
                    : number_format($product->price, 2);
            @endphp
            <button
                type="button"
                wire:click="addProduct({{ $product->id }})"
                wire:loading.attr="disabled"
                wire:key="product-{{ $product->id }}"
                class="pos-product-tile"
            >
                <span class="pos-product-tile-thumb">
                    @if ($product->primaryImageUrl())
                        <img src="{{ $product->primaryImageUrl() }}" alt="" loading="lazy">
                    @else
                        <span class="pos-product-tile-thumb-empty">
                            <x-ui.icon name="building-storefront" class="h-5 w-5" />
                        </span>
                    @endif
                </span>

                <span class="pos-product-tile-body">
                    <span class="pos-product-tile-name">{{ $product->name }}</span>
                    <span class="pos-product-tile-meta">
                        @if ($showCategory && $product->relationLoaded('category') && $product->category)
                            <span class="pos-product-tile-cat">{{ $product->category->name }}</span>
                        @endif
                        <span class="pos-product-tile-price">{{ $priceLabel }} {{ $currency }}</span>
                    </span>
                    @if ($hasOptions)
                        <span class="pos-product-tile-badge">خيارات</span>
                    @endif
                </span>

                <span class="pos-product-tile-add" aria-hidden="true">+</span>
            </button>
        @empty
            <p class="pos-empty">لا توجد أطباق مطابقة.</p>
        @endforelse
    </div>
</div>
