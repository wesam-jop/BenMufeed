<div class="inv-page">
    <header class="inv-page-header">
        <div class="inv-page-brand">
            <span class="inv-page-icon"><x-ui.icon name="document-text" class="h-6 w-6" /></span>
            <div>
                <p class="inv-page-title">بن مفيد</p>
                <p class="inv-page-sub">المبيعات — جرد يومي</p>
            </div>
        </div>
        <div class="inv-page-actions">
            <a href="{{ route('pos.cashier') }}" class="inv-nav-link">كاشير</a>
            <a href="{{ url('/admin') }}" class="inv-nav-link">لوحة التحكم</a>
        </div>
    </header>

    <section class="inv-form-card mb-4">
        <div class="inv-form-head">
            <div>
                <h1 class="inv-form-title">جرد يوم {{ $report['date'] }}</h1>
                <p class="inv-form-sub">ملخّص مبيعات الكاشير والأطباق المباعة — للمطابقة مع الصندوق والمخزون.</p>
            </div>
        </div>
        <div class="inv-section">
            <div class="sales-day-nav">
                <button type="button" wire:click="goToPreviousDay" class="profit-preset inline-flex items-center gap-1">
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    اليوم السابق
                </button>
                <button type="button" wire:click="goToToday" @class(['profit-preset', 'profit-preset-active' => $isToday])>اليوم</button>
                <button type="button" wire:click="goToNextDay" class="profit-preset inline-flex items-center gap-1" @disabled($isToday)>
                    اليوم التالي
                    <x-ui.icon name="arrow-left" class="h-4 w-4" />
                </button>
            </div>
            <div class="inv-fields-grid fin-fields-grid mt-4 max-w-sm">
                <div class="inv-field">
                    <label class="inv-label">تاريخ الجرد</label>
                    <input type="date" wire:model.live="date" class="inv-input">
                </div>
            </div>
        </div>
    </section>

    @php
        $summary = $report['summary'];
    @endphp

    <div @class(['profit-hero', 'profit-hero-positive' => $summary['grand_total'] > 0])>
        <div class="profit-hero-main">
            <span class="profit-hero-label">إجمالي مبيعات اليوم</span>
            <strong class="profit-hero-value">{{ number_format($summary['grand_total'], 2) }} {{ $currency }}</strong>
            <span class="profit-hero-margin">مقبوض POS: {{ number_format($summary['collected_total'], 2) }} {{ $currency }}</span>
        </div>
        <div class="profit-hero-formula">
            <span>POS: {{ number_format($summary['pos_total'], 2) }}</span>
            <span class="profit-hero-minus">+</span>
            <span>فواتير مبيع: {{ number_format($summary['invoice_total'], 2) }}</span>
        </div>
    </div>

    <div class="fin-stats profit-stats-grid">
        <div class="fin-stat fin-stat-in">
            <span class="fin-stat-label">فواتير POS</span>
            <strong>{{ $summary['orders_count'] }}</strong>
            <span class="fin-stat-sub">{{ number_format($summary['pos_total'], 2) }} {{ $currency }}</span>
        </div>
        <div class="fin-stat fin-stat-in">
            <span class="fin-stat-label">سفري</span>
            <strong>{{ $summary['takeaway_count'] }}</strong>
            <span class="fin-stat-sub">{{ number_format($summary['takeaway_total'], 2) }} {{ $currency }}</span>
        </div>
        <div class="fin-stat fin-stat-in">
            <span class="fin-stat-label">طاولات (مغلقة)</span>
            <strong>{{ $summary['table_count'] }}</strong>
            <span class="fin-stat-sub">{{ number_format($summary['table_total'], 2) }} {{ $currency }}</span>
        </div>
        <div class="fin-stat fin-stat-net">
            <span class="fin-stat-label">فواتير مبيع (مواد)</span>
            <strong>{{ $summary['invoice_count'] }}</strong>
            <span class="fin-stat-sub">{{ number_format($summary['invoice_total'], 2) }} {{ $currency }}</span>
        </div>
    </div>

    @if ($summary['open_tables_count'] > 0)
        <section class="inv-form-card mb-4 sales-open-tables-alert">
            <div class="inv-section">
                <h2 class="inv-section-title inline-flex items-center gap-2">
                    <x-ui.icon name="exclamation-triangle" class="h-5 w-5 text-amber-600" />
                    طاولات مفتوحة (لم تُغلق بعد)
                </h2>
                <p class="inv-form-sub mb-3">هذه الطاولات لا تدخل في جرد اليوم حتى يتم إغلاقها من الكاشير.</p>
                <div class="fin-stats profit-stats-grid">
                    @foreach ($report['open_tables'] as $openOrder)
                        <div class="fin-stat fin-stat-out" wire:key="open-table-{{ $openOrder->id }}">
                            <span class="fin-stat-label">طاولة {{ $openOrder->table_number }}</span>
                            <strong>{{ number_format($openOrder->total, 2) }} {{ $currency }}</strong>
                            <span class="fin-stat-sub">#{{ $openOrder->id }} · {{ $openOrder->items_count }} صنف</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="inv-form-card mb-4">
        <div class="inv-form-head">
            <div>
                <h2 class="inv-form-title text-lg">جرد الأطباق المباعة</h2>
                <p class="inv-form-sub">كميات كل طبق — للمطابقة مع المخزون والاستهلاك.</p>
            </div>
        </div>
        <div class="inv-table-wrap inv-section">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الطبق</th>
                        <th>الكمية المباعة</th>
                        <th>إجمالي المبيع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['products'] as $index => $product)
                        <tr wire:key="product-{{ $product->product_id ?? $index }}-{{ $product->product_name }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $product->product_name }}</td>
                            <td><strong>{{ $product->total_qty }}</strong></td>
                            <td>{{ number_format($product->total_revenue, 2) }} {{ $currency }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="inv-empty-cell">لا توجد مبيعات POS في هذا اليوم.</td></tr>
                    @endforelse
                </tbody>
                @if ($report['products']->isNotEmpty())
                    <tfoot>
                        <tr class="sales-table-foot">
                            <td colspan="2">المجموع</td>
                            <td><strong>{{ $report['products']->sum('total_qty') }}</strong></td>
                            <td><strong>{{ number_format($report['products']->sum('total_revenue'), 2) }} {{ $currency }}</strong></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    <section class="inv-form-card mb-4">
        <div class="inv-form-head">
            <div>
                <h2 class="inv-form-title text-lg">فواتير الكاشير</h2>
                <p class="inv-form-sub">كل فاتورة مكتملة في هذا اليوم.</p>
            </div>
        </div>
        <div class="inv-table-wrap inv-section">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الوقت</th>
                        <th>النوع</th>
                        <th>الكاشير</th>
                        <th>الأصناف</th>
                        <th>الإجمالي</th>
                        <th>مقبوض</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['orders'] as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td>{{ $order->id }}</td>
                            <td>{{ ($order->closed_at ?? $order->created_at)->format('H:i') }}</td>
                            <td>
                                {{ $order->service_type?->label() ?? '—' }}
                                @if ($order->table_number)
                                    <span class="fin-ref">({{ $order->table_number }})</span>
                                @endif
                            </td>
                            <td>{{ $order->cashier?->name ?? '—' }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td>{{ number_format($order->total, 2) }} {{ $currency }}</td>
                            <td>{{ number_format((float) ($order->paid_amount ?? $order->total), 2) }} {{ $currency }}</td>
                            <td class="inv-row-actions">
                                <a href="{{ route('pos.invoices.edit', $order) }}" class="inv-btn-link">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="inv-empty-cell">لا توجد فواتير POS في هذا اليوم.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($report['invoices']->isNotEmpty())
        <section class="inv-form-card">
            <div class="inv-form-head">
                <div>
                    <h2 class="inv-form-title text-lg">فواتير مبيع المواد</h2>
                    <p class="inv-form-sub">مبيعات مخزون عبر فواتير المبيع (غير POS).</p>
                </div>
            </div>
            <div class="inv-table-wrap inv-section">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>الفاتورة</th>
                            <th>العميل</th>
                            <th>الدفع</th>
                            <th>الإجمالي</th>
                            <th>مدفوع</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['invoices'] as $invoice)
                            <tr wire:key="inv-{{ $invoice->id }}">
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer?->name ?? $invoice->customer_name ?: '—' }}</td>
                                <td>{{ $invoice->payment_type?->label() ?? 'نقدي' }}</td>
                                <td>{{ number_format($invoice->total, 2) }} {{ $currency }}</td>
                                <td>{{ number_format($invoice->paid_amount, 2) }} {{ $currency }}</td>
                                <td class="inv-row-actions">
                                    <a href="{{ route('invoices.sales', $invoice) }}" class="inv-btn-link">عرض</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
