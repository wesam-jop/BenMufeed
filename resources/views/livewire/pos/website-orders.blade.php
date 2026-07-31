<div class="pos-shell pos-page" wire:poll.4s.visible="pollPending">
    <x-pos.header :open-tables-count="count($openTablesCount)" />

    @if ($flashMessage)
        <div class="pos-toast pos-toast-success">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                {{ $flashMessage }}
            </span>
            <button type="button" wire:click="$set('flashMessage', null)" class="pos-toast-close">×</button>
        </div>
    @endif

    @error('workflow')
        <div class="pos-toast pos-toast-error">{{ $message }}</div>
    @enderror

    <main class="pos-page-main">
        <div class="pos-page-head">
            <div>
                <h1 class="pos-page-title">طلبات اونلاين</h1>
                <p class="pos-page-sub">طلبات الزبائن من المتجر الإلكتروني — قبول للمطبخ أو رفض</p>
            </div>
            <a href="{{ route('pos.cashier') }}" class="pos-history-btn">العودة للكاشير</a>
        </div>

        @if (count($pending) === 0)
            <div class="pos-page-card pos-website-empty">
                <p class="pos-website-empty-title">لا طلبات جديدة</p>
                <p class="pos-website-empty-sub">عند وصول طلب من الموقع يظهر تنبيه صوتي ويمكنك معالجته من هنا.</p>
            </div>
        @else
            <div class="pos-website-orders-grid">
                @foreach ($pending as $order)
                    <article wire:key="website-order-{{ $order['id'] }}" @class(['pos-website-card', 'pos-website-card-delivery' => $order['is_delivery']])>
                        <div class="pos-website-card-head">
                            <span class="pos-website-card-id">#{{ $order['id'] }}</span>
                            <span @class(['pos-website-service-tag', 'pos-website-service-delivery' => $order['is_delivery']])>
                                {{ $order['service'] }}
                            </span>
                        </div>
                        <p class="pos-website-card-line"><strong>{{ $order['customer'] }}</strong> — {{ $order['phone'] }}</p>
                        <p class="pos-website-card-line">{{ $order['items_count'] }} صنف — {{ $order['total'] }} $ · {{ $order['created_at'] }}</p>
                        @if ($order['location_note'])
                            <p class="pos-website-card-note">
                                <x-pos.icon name="map-pin" class="pos-icon pos-icon-sm pos-note-icon" />
                                <span>{{ $order['location_note'] }}</span>
                            </p>
                        @endif

                        <div class="pos-website-card-actions">
                            <button type="button" class="pos-history-btn" wire:click="showDetail({{ $order['id'] }})">
                                عرض التفاصيل
                            </button>
                            <button type="button" class="pos-checkout-btn" wire:click="accept({{ $order['id'] }})">
                                قبول — للمطبخ
                            </button>
                            <button type="button" class="pos-history-btn pos-history-btn-danger" wire:click="startReject({{ $order['id'] }})">
                                رفض
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>

    @if ($detailOrder)
        <div class="pos-modal-backdrop" wire:key="website-order-detail">
            <div class="pos-modal pos-modal-wide">
                <div class="pos-modal-head">
                    <div>
                        <h3 class="pos-modal-title">طلب #{{ $detailOrder['id'] }}</h3>
                        <p class="pos-website-detail-sub">{{ $detailOrder['service'] }} · {{ $detailOrder['created_at'] }}</p>
                    </div>
                    <button type="button" wire:click="closeDetail" class="pos-modal-close">×</button>
                </div>

                <div class="pos-website-detail-grid">
                    <section class="pos-website-detail-block">
                        <h4 class="pos-website-detail-label">الزبون</h4>
                        <p class="pos-website-detail-line"><strong>{{ $detailOrder['customer'] }}</strong></p>
                        <p class="pos-website-detail-line">
                            <a href="tel:{{ $detailOrder['phone'] }}" class="pos-website-phone-link">{{ $detailOrder['phone'] }}</a>
                        </p>
                    </section>

                    @if ($detailOrder['is_delivery'])
                        <section class="pos-website-detail-block">
                            <h4 class="pos-website-detail-label">التوصيل</h4>
                            @if ($detailOrder['location_note'])
                                <p class="pos-website-detail-line">{{ $detailOrder['location_note'] }}</p>
                            @endif
                            @if ($detailOrder['map_url'])
                                <a href="{{ $detailOrder['map_url'] }}" target="_blank" rel="noopener" class="pos-website-map-btn">
                                    <x-pos.icon name="map-pin" class="pos-icon pos-icon-sm" />
                                    فتح على Google Maps
                                </a>
                            @else
                                <p class="pos-website-detail-muted">لم يُحدَّد موقع GPS على الخريطة.</p>
                            @endif
                        </section>
                    @endif
                </div>

                @if ($detailOrder['note'])
                    <section class="pos-website-detail-block">
                        <h4 class="pos-website-detail-label">ملاحظة الزبون</h4>
                        <p class="pos-website-detail-line">{{ $detailOrder['note'] }}</p>
                    </section>
                @endif

                <section class="pos-website-detail-block">
                    <h4 class="pos-website-detail-label">الأصناف</h4>
                    <ul class="pos-website-detail-items">
                        @foreach ($detailOrder['items'] as $item)
                            <li class="pos-website-detail-item" wire:key="detail-item-{{ $detailOrder['id'] }}-{{ $loop->index }}">
                                <div class="pos-website-detail-item-head">
                                    <span class="pos-website-detail-qty">×{{ $item['quantity'] }}</span>
                                    <span class="pos-website-detail-name">{{ $item['label'] }}</span>
                                    <span class="pos-website-detail-price">{{ $item['line_total'] }} {{ $detailOrder['currency'] }}</span>
                                </div>
                                @if (! empty($item['addons']))
                                    <ul class="pos-website-detail-addons">
                                        @foreach ($item['addons'] as $addon)
                                            <li wire:key="detail-addon-{{ $loop->parent->index }}-{{ $loop->index }}">+ {{ $addon }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                <div class="pos-website-detail-totals">
                    @if ($detailOrder['is_delivery'] && (float) str_replace(',', '', $detailOrder['delivery_fee']) > 0)
                        <div class="pos-website-detail-total-row">
                            <span>المجموع الفرعي</span>
                            <span>{{ $detailOrder['subtotal'] }} {{ $detailOrder['currency'] }}</span>
                        </div>
                        <div class="pos-website-detail-total-row">
                            <span>رسوم التوصيل</span>
                            <span>{{ $detailOrder['delivery_fee'] }} {{ $detailOrder['currency'] }}</span>
                        </div>
                    @endif
                    <div class="pos-website-detail-total-row pos-website-detail-total-row--grand">
                        <span>الإجمالي</span>
                        <strong>{{ $detailOrder['total'] }} {{ $detailOrder['currency'] }}</strong>
                    </div>
                </div>

                @if ($rejectingOrderId === $detailOrder['id'])
                    <div class="pos-website-reject-form">
                        <label class="pos-website-reject-label">سبب الرفض (اختياري)</label>
                        <textarea wire:model="rejectReason" class="pos-website-reject-input" rows="2" placeholder="مثال: خارج نطاق التوصيل"></textarea>
                        <div class="pos-website-card-actions">
                            <button type="button" class="pos-history-btn pos-history-btn-danger" wire:click="reject">تأكيد الرفض</button>
                            <button type="button" class="pos-history-btn" wire:click="cancelReject">إلغاء</button>
                        </div>
                    </div>
                @else
                    <div class="pos-modal-actions">
                        @if ($detailOrder['map_url'])
                            <a href="{{ $detailOrder['map_url'] }}" target="_blank" rel="noopener" class="pos-history-btn">
                                Google Maps
                            </a>
                        @endif
                        <button type="button" class="pos-checkout-btn" wire:click="accept({{ $detailOrder['id'] }})">
                            قبول — للمطبخ
                        </button>
                        <button type="button" class="pos-history-btn pos-history-btn-danger" wire:click="startReject({{ $detailOrder['id'] }})">
                            رفض
                        </button>
                        <button type="button" class="pos-clear-btn" wire:click="closeDetail">إغلاق</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($rejectingOrderId && (! $detailOrder || $rejectingOrderId !== ($detailOrder['id'] ?? null)))
        @php $rejectOrder = collect($pending)->firstWhere('id', $rejectingOrderId); @endphp
        @if ($rejectOrder)
            <div class="pos-modal-backdrop" wire:key="website-reject-inline">
                <div class="pos-modal">
                    <div class="pos-modal-head">
                        <h3 class="pos-modal-title">رفض الطلب #{{ $rejectOrder['id'] }}</h3>
                        <button type="button" wire:click="cancelReject" class="pos-modal-close">×</button>
                    </div>
                    <div class="pos-website-reject-form">
                        <label class="pos-website-reject-label">سبب الرفض (اختياري)</label>
                        <textarea wire:model="rejectReason" class="pos-website-reject-input" rows="3" placeholder="مثال: خارج نطاق التوصيل"></textarea>
                        <div class="pos-website-card-actions">
                            <button type="button" class="pos-history-btn pos-history-btn-danger" wire:click="reject">تأكيد الرفض</button>
                            <button type="button" class="pos-history-btn" wire:click="cancelReject">إلغاء</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
