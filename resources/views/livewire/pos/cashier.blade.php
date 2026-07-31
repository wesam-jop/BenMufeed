<div class="pos-shell">
    <x-pos.header :open-tables-count="$openTablesCount">
        @if ($this->serviceReady)
            <x-slot:headerCenter>
                <div class="pos-search-wrap">
                    <svg class="pos-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden>
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/>
                    </svg>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        class="pos-search"
                        placeholder="ابحث عن طبق..."
                        autocomplete="off"
                    >
                </div>
            </x-slot:headerCenter>
        @endif
    </x-pos.header>

    @if ($lastActionMessage)
        <div class="pos-toast pos-toast-success" wire:key="toast-msg">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                {{ $lastActionMessage }}
            </span>
            <button type="button" wire:click="$set('lastActionMessage', null)" class="pos-toast-close">×</button>
        </div>
    @endif

    @error('cart')
        <div class="pos-toast pos-toast-error">{{ $message }}</div>
    @enderror

    @error('close')
        <div class="pos-toast pos-toast-error">{{ $message }}</div>
    @enderror

    @if (! $this->serviceReady)
        <main class="pos-setup">
            <div class="pos-setup-inner">
                <header class="pos-setup-hero">
                    <p class="pos-setup-eyebrow">كاشير — بن مفيد</p>
                    <h1 class="pos-setup-title">اختر مسار الطلب</h1>
                    <p class="pos-setup-sub">طلبات المتجر الإلكتروني تُدار من البطاقة الأولى أو من لوحة «طلبات اونلاين»</p>
                </header>

                <div class="pos-setup-modes" wire:poll.8s.visible="refreshWebsiteOrderCount">
                    <a href="{{ route('pos.website-orders') }}" @class(['pos-mode-card', 'pos-mode-card--website', 'pos-mode-card--pulse' => $pendingWebsiteCount > 0])>
                        <span class="pos-mode-card__glow" aria-hidden="true"></span>
                        <span class="pos-mode-card__icon pos-mode-card__icon--website">
                            <x-pos.icon name="globe-alt" class="pos-icon pos-icon-lg" />
                        </span>
                        <span class="pos-mode-card__body">
                            <span class="pos-mode-card__title">طلبات اونلاين</span>
                                <span class="pos-mode-card__subtitle">بيع سفر بدون طاولة · اونلاين مع توصيل</span>
                            @if ($pendingWebsiteCount > 0)
                                <span class="pos-mode-card__badge">
                                    <x-pos.icon name="bell-alert" class="pos-icon pos-icon-sm" />
                                    {{ $pendingWebsiteCount }} بانتظار القبول
                                </span>
                            @else
                                <span class="pos-mode-card__hint">لا طلبات جديدة — يصل تنبيه صوتي عند الطلب</span>
                            @endif
                        </span>
                        <x-pos.icon name="arrow-left" class="pos-mode-card__chevron" />
                    </a>

                    <button type="button" wire:click="startTakeaway" class="pos-mode-card pos-mode-card--takeaway">
                        <span class="pos-mode-card__icon pos-mode-card__icon--takeaway">
                            <x-pos.icon name="shopping-bag" class="pos-icon pos-icon-lg" />
                        </span>
                        <span class="pos-mode-card__body">
                            <span class="pos-mode-card__title">بيع سفر بدون طاولة</span>
                            <span class="pos-mode-card__subtitle">بيع مباشر من الكاشير</span>
                            <span class="pos-mode-card__hint">للزبون الحاضر — ليس طلب اونلاين</span>
                        </span>
                        <x-pos.icon name="arrow-left" class="pos-mode-card__chevron" />
                    </button>

                    <section @class(['pos-mode-card pos-mode-card--tables', 'pos-mode-card--tables-active' => $serviceType === 'table'])>
                        <div class="pos-mode-card__tables-head">
                            <span class="pos-mode-card__icon pos-mode-card__icon--table">
                                <x-pos.icon name="table-cells" class="pos-icon pos-icon-lg" />
                            </span>
                            <div class="pos-mode-card__body">
                                <span class="pos-mode-card__title">الطاولات</span>
                                <span class="pos-mode-card__subtitle">اختر رقم الطاولة لبدء أو متابعة الفاتورة</span>
                            </div>
                        </div>

                        @if ($restaurantTables->isEmpty())
                            <p class="pos-tables-empty py-6">لا توجد طاولات — أضفها من لوحة التحكم → الطاولات.</p>
                        @else
                            <div class="pos-setup-table-grid pos-mode-card__table-grid">
                                @foreach ($restaurantTables as $restaurantTable)
                                    @php $occupied = in_array($restaurantTable->number, $occupiedTables, true); @endphp
                                    <button
                                        type="button"
                                        wire:click="selectTable({{ $restaurantTable->number }})"
                                        @class([
                                            'pos-setup-table-chip',
                                            'pos-setup-table-chip-active' => $tableNumber === $restaurantTable->number,
                                            'pos-setup-table-chip-occupied' => $occupied,
                                        ])
                                        wire:key="setup-table-{{ $restaurantTable->id }}"
                                        title="{{ $occupied ? 'طاولة مفتوحة — فتح الفاتورة' : $restaurantTable->displayName() }}"
                                    >
                                        <span class="pos-setup-table-chip-num">{{ $restaurantTable->number }}</span>
                                        @if ($restaurantTable->label)
                                            <span class="pos-setup-table-chip-label">{{ $restaurantTable->label }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                            @if ($tableNumber && in_array($tableNumber, $occupiedTables, true))
                                <p class="pos-table-hint">طاولة {{ $tableNumber }} مفتوحة — اضغط لفتح الفاتورة وتعديلها أو إغلاقها.</p>
                            @endif

                            @if ($tableNumber && $restaurantTables->contains('number', $tableNumber))
                                <button type="button" wire:click="selectTable({{ $tableNumber }})" class="pos-checkout-btn mt-4 py-4 text-lg">
                                    @if (in_array($tableNumber, $occupiedTables, true))
                                        فتح فاتورة طاولة {{ $tableNumber }}
                                    @else
                                        متابعة — طاولة {{ $tableNumber }}
                                    @endif
                                </button>
                            @endif
                        @endif
                    </section>
                </div>
            </div>
        </main>
    @else
        <div class="pos-main">
            <section class="pos-products-panel">
                <div class="pos-catalog-toolbar">
                    <div class="pos-categories">
                        <button type="button" wire:click="selectCategory(null)" @class(['pos-cat-btn', 'pos-cat-btn-active' => $categoryId === null])>الكل</button>
                        @foreach ($categories as $category)
                            <button type="button" wire:click="selectCategory({{ $category->id }})" @class(['pos-cat-btn', 'pos-cat-btn-active' => $categoryId === $category->id])>{{ $category->name }}</button>
                        @endforeach
                    </div>
                </div>

                <x-pos.product-grid
                    :products="$products"
                    :currency="$currency"
                    :show-category="$categoryId === null"
                />
            </section>

            <aside class="pos-cart-panel">
                <div class="pos-cart-header">
                    <div>
                        <h2 class="pos-cart-title">السلة</h2>
                        <p class="pos-service-badge">
                            @if ($serviceType === 'takeaway')
                                <x-pos.icon name="shopping-bag" class="pos-icon pos-icon-sm" />
                                <span>بيع سفر بدون طاولة</span>
                            @elseif ($this->isEditingTable)
                                <x-pos.icon name="table-cells" class="pos-icon pos-icon-sm" />
                                <span>تعديل طاولة {{ $tableNumber }} — #{{ $editingOrderId }}</span>
                            @else
                                <x-pos.icon name="table-cells" class="pos-icon pos-icon-sm" />
                                <span>طاولة {{ $tableNumber }} — طلب جديد</span>
                            @endif
                        </p>
                    </div>
                    <div class="pos-cart-header-end">
                        <span class="pos-cart-badge">{{ $this->cartCount }}</span>
                        <button type="button" wire:click="changeService" wire:confirm="{{ $cart !== [] ? 'سيتم مسح السلة والعودة لاختيار نوع الطلب.' : 'العودة لاختيار نوع الطلب؟' }}" class="pos-change-service-btn">تغيير</button>
                    </div>
                </div>

                @if ($serviceType === 'takeaway')
                    <div class="pos-cart-field">
                        <input type="text" wire:model="customerName" class="pos-input" placeholder="اسم العميل (اختياري)">
                    </div>
                @endif

                <div class="pos-cart-items">
                    @forelse ($cart as $lineKey => $item)
                        <div class="pos-cart-item" wire:key="cart-{{ $lineKey }}">
                            <div class="pos-cart-item-info">
                                <p class="pos-cart-item-name">{{ $item['name'] }}</p>
                                <p class="pos-cart-item-price">{{ number_format($item['price'], 2) }} {{ $currency }}</p>
                            </div>
                            <div class="pos-qty-controls">
                                <button type="button" wire:click="decrement('{{ $lineKey }}')" class="pos-qty-btn">−</button>
                                <span class="pos-qty">{{ $item['quantity'] }}</span>
                                <button type="button" wire:click="increment('{{ $lineKey }}')" class="pos-qty-btn">+</button>
                            </div>
                            <p class="pos-cart-line-total">{{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                            <button type="button" wire:click="removeItem('{{ $lineKey }}')" class="pos-remove-btn" aria-label="حذف">×</button>
                        </div>
                    @empty
                        <div class="pos-cart-empty">
                            <span class="pos-cart-empty-icon">
                                <x-ui.icon name="shopping-bag" class="h-12 w-12" />
                            </span>
                            <p>اضغط على طبق لإضافته</p>
                        </div>
                    @endforelse
                </div>

                <div class="pos-cart-footer">
                    <input type="text" wire:model="customerNote" class="pos-input" placeholder="ملاحظة (اختياري)">

                    <div class="pos-total-row">
                        <span>الإجمالي</span>
                        <strong>{{ number_format($this->cartTotal, 2) }} {{ $currency }}</strong>
                    </div>

                    @if ($serviceType === 'table')
                        @if ($this->isEditingTable)
                            <button type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout" @disabled($cart === []) class="pos-checkout-btn">
                                <span wire:loading.remove wire:target="checkout">حفظ تعديلات الطاولة {{ $tableNumber }}</span>
                                <span wire:loading wire:target="checkout">جاري الحفظ...</span>
                            </button>

                            <div class="pos-table-close-section">
                                <p class="pos-close-table-total">المطلوب: <strong>{{ number_format($this->cartTotal, 2) }} {{ $currency }}</strong></p>
                                <label class="pos-close-label">المبلغ المقبوض من الزبون</label>
                                <input type="number" step="0.01" min="0" wire:model="closingPaidAmount" class="pos-input pos-setup-table-input">
                                <button
                                    type="button"
                                    wire:click="closeTable"
                                    wire:loading.attr="disabled"
                                    wire:target="closeTable"
                                    wire:confirm="إغلاق طاولة {{ $tableNumber }} وتسجيل المبلغ المقبوض؟"
                                    @disabled($cart === [])
                                    class="pos-close-table-btn"
                                >
                                    <span wire:loading.remove wire:target="closeTable">إغلاق الطاولة {{ $tableNumber }}</span>
                                    <span wire:loading wire:target="closeTable">جاري الإغلاق...</span>
                                </button>
                            </div>
                        @else
                            <button type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout" @disabled($cart === []) class="pos-checkout-btn">
                                <span wire:loading.remove wire:target="checkout">إرسال للطاولة {{ $tableNumber }}</span>
                                <span wire:loading wire:target="checkout">جاري الإرسال...</span>
                            </button>
                        @endif
                    @else
                        <button type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout" @disabled($cart === []) class="pos-checkout-btn">
                            <span wire:loading.remove wire:target="checkout">تأكيد البيع (سفر بدون طاولة)</span>
                            <span wire:loading wire:target="checkout">جاري الحفظ...</span>
                        </button>
                    @endif

                    @if ($cart !== [])
                        <button type="button" wire:click="clearCart" class="pos-clear-btn">
                            {{ $this->isEditingTable ? 'تراجع عن التعديل' : 'مسح السلة' }}
                        </button>
                    @endif
                </div>
            </aside>
        </div>
    @endif

    @if ($customizingProduct)
        <div class="pos-modal-backdrop" wire:key="pos-customizer">
            <div class="pos-modal">
                <div class="pos-modal-head">
                    <h3 class="pos-modal-title">{{ $customizingProduct['name'] }}</h3>
                    <button type="button" wire:click="cancelCustomizer" class="pos-modal-close">×</button>
                </div>

                @if (! empty($customizingProduct['variants']))
                    <p class="pos-modal-label">اختر النوع *</p>
                    <div class="pos-modal-options">
                        @foreach ($customizingProduct['variants'] as $variant)
                            <button
                                type="button"
                                wire:click="selectCustomizerVariant({{ $variant['id'] }})"
                                @class(['pos-modal-chip', 'pos-modal-chip-active' => $customizerVariantId === $variant['id']])
                            >
                                {{ $variant['name'] }} — {{ number_format($variant['price'], 2) }} {{ $currency }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @if (! empty($customizingProduct['addons']))
                    <p class="pos-modal-label">إضافات</p>
                    <div class="pos-modal-options">
                        @foreach ($customizingProduct['addons'] as $addon)
                            @php $qty = (int) ($customizerAddonQty[$addon['id']] ?? 0); @endphp
                            <button
                                type="button"
                                wire:click="toggleCustomizerAddon({{ $addon['id'] }})"
                                @class(['pos-modal-chip', 'pos-modal-chip-active' => $qty > 0])
                            >
                                {{ $addon['name'] }} +{{ number_format($addon['price'], 2) }} {{ $currency }}
                                @if ($qty > 1)
                                    ×{{ $qty }}
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="pos-modal-actions">
                    <button type="button" wire:click="confirmCustomizer" class="pos-checkout-btn">إضافة للسلة</button>
                    <button type="button" wire:click="cancelCustomizer" class="pos-clear-btn">إلغاء</button>
                </div>
            </div>
        </div>
    @endif
</div>
