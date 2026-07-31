<div class="inv-page">
    <header class="inv-page-header">
        <div class="inv-page-brand">
            <span class="inv-page-icon"><x-ui.icon name="chart-bar" class="h-6 w-6" /></span>
            <div>
                <p class="inv-page-title">بن مفيد</p>
                <p class="inv-page-sub">الأرباح — حساب لحظي</p>
            </div>
        </div>
        <div class="inv-page-actions">
            <a href="{{ url('/admin') }}" class="inv-nav-link">لوحة التحكم</a>
        </div>
    </header>

    <section class="inv-form-card mb-4">
        <div class="inv-form-head">
            <div>
                <h1 class="inv-form-title">فترة الحساب</h1>
                <p class="inv-form-sub">اختر الفترة — الأرقام تتحدّث فوراً عند تغيير التاريخ.</p>
            </div>
        </div>
        <div class="inv-section">
            <div class="profit-presets">
                <button type="button" wire:click="applyPreset('today')" @class(['profit-preset', 'profit-preset-active' => $preset === 'today'])>اليوم</button>
                <button type="button" wire:click="applyPreset('month')" @class(['profit-preset', 'profit-preset-active' => $preset === 'month'])>هذا الشهر</button>
                <button type="button" wire:click="applyPreset('last_month')" @class(['profit-preset', 'profit-preset-active' => $preset === 'last_month'])>الشهر الماضي</button>
                <button type="button" wire:click="applyPreset('year')" @class(['profit-preset', 'profit-preset-active' => $preset === 'year'])>هذه السنة</button>
                <button type="button" wire:click="applyPreset('all')" @class(['profit-preset', 'profit-preset-active' => $preset === 'all'])>كل الفترة</button>
            </div>
            <div class="inv-fields-grid fin-fields-grid mt-4">
                <div class="inv-field">
                    <label class="inv-label">من تاريخ</label>
                    <input type="date" wire:model.live="from" class="inv-input">
                </div>
                <div class="inv-field">
                    <label class="inv-label">إلى تاريخ</label>
                    <input type="date" wire:model.live="to" class="inv-input">
                </div>
            </div>
        </div>
    </section>

    @php
        $profit = $summary['profit'];
        $sales = $summary['sales'];
        $payroll = $summary['payroll'];
        $debts = $summary['debts'];
        $ownerWithdrawals = $profit['owner_withdrawals'];
        $isPositive = $profit['operating'] >= 0;
        $isPositiveAfter = $profit['net_after_withdrawals'] >= 0;
    @endphp

    <div @class(['profit-hero', 'profit-hero-positive' => $isPositive, 'profit-hero-negative' => ! $isPositive])>
        <div class="profit-hero-main">
            <span class="profit-hero-label">صافي الربح التشغيلي</span>
            <strong class="profit-hero-value">{{ number_format($profit['operating'], 2) }} {{ $currency }}</strong>
            @if ($profit['margin_percent'] !== null)
                <span class="profit-hero-margin">هامش {{ $profit['margin_percent'] }}%</span>
            @endif
        </div>
        <div class="profit-hero-formula">
            <span>إجمالي الدخل {{ number_format($profit['total_income'], 2) }}</span>
            <span class="profit-hero-minus">−</span>
            <span>إجمالي التكاليف {{ number_format($profit['total_costs'], 2) }}</span>
        </div>
    </div>

    <div @class(['profit-hero profit-hero-secondary', 'profit-hero-positive' => $isPositiveAfter, 'profit-hero-negative' => ! $isPositiveAfter])>
        <div class="profit-hero-main">
            <span class="profit-hero-label">الربح المتبقي (بعد مسحوبات الشريك)</span>
            <strong class="profit-hero-value profit-hero-value-sm">{{ number_format($profit['net_after_withdrawals'], 2) }} {{ $currency }}</strong>
        </div>
        <div class="profit-hero-formula">
            <span>ربح تشغيلي {{ number_format($profit['operating'], 2) }}</span>
            <span class="profit-hero-minus">−</span>
            <span>مسحوبات {{ number_format($ownerWithdrawals, 2) }}</span>
        </div>
    </div>

    <div class="fin-stats profit-stats-grid">
        <div class="fin-stat fin-stat-in">
            <span class="fin-stat-label">المبيعات</span>
            <strong>{{ number_format($sales['total'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">POS: {{ number_format($sales['pos'], 2) }} · فواتير: {{ number_format($sales['invoices'], 2) }}</span>
        </div>
        <div class="fin-stat fin-stat-out">
            <span class="fin-stat-label">المشتريات</span>
            <strong>{{ number_format($summary['purchases'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">فواتير شراء مؤكدة</span>
        </div>
        <div class="fin-stat fin-stat-out">
            <span class="fin-stat-label">المصاريف</span>
            <strong>{{ number_format($summary['expenses'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">مصاريف تشغيل (بدون رواتب)</span>
        </div>
        <div class="fin-stat fin-stat-out">
            <span class="fin-stat-label">الرواتب والسلف</span>
            <strong>{{ number_format($payroll['total_out'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">دفعات: {{ number_format($payroll['paid'], 2) }} · سلف: {{ number_format($payroll['advances'], 2) }}</span>
        </div>
    </div>

    <div class="fin-stats profit-stats-grid">
        <div class="fin-stat fin-stat-net">
            <span class="fin-stat-label">الربح الإجمالي</span>
            <strong>{{ number_format($profit['gross'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">المبيعات − المشتريات</span>
        </div>
        <div class="fin-stat fin-stat-in">
            <span class="fin-stat-label">قبض خارجي</span>
            <strong>{{ number_format($summary['other_income']['external_receipts'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">دخل إضافي</span>
        </div>
        <div class="fin-stat fin-stat-out">
            <span class="fin-stat-label">دفعات إلى عملاء/موردين</span>
            <strong>{{ number_format($summary['other_outgoing']['customer_payments'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">تسديد ديون (حركة مالية)</span>
        </div>
        <div class="fin-stat fin-stat-withdrawal">
            <span class="fin-stat-label">مسحوبات الشريك</span>
            <strong>{{ number_format($ownerWithdrawals, 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">سحب من الربح — ليس مصروفاً</span>
        </div>
    </div>

    <div class="fin-stats profit-stats-grid">
        <div class="fin-stat fin-stat-net">
            <span class="fin-stat-label">استحقاق رواتب (دائن)</span>
            <strong>{{ number_format($payroll['accrued'], 2) }} {{ $currency }}</strong>
            <span class="fin-stat-sub">متبقٍ للعمال: {{ number_format($payroll['outstanding_liability'], 2) }} {{ $currency }}</span>
        </div>
    </div>

    <section class="inv-form-card">
        <div class="inv-form-head">
            <div>
                <h2 class="inv-form-title text-lg">الديون والمخزون</h2>
                <p class="inv-form-sub">أرصدة لحظية — لا تتأثر بفلتر التاريخ.</p>
            </div>
        </div>
        <div class="fin-stats profit-stats-grid inv-section">
            <div class="fin-stat fin-stat-out">
                <span class="fin-stat-label">الديون علينا</span>
                <strong>{{ number_format($debts['payable'], 2) }} {{ $currency }}</strong>
                <span class="fin-stat-sub">مستحق للموردين (فواتير شراء دين)</span>
            </div>
            <div class="fin-stat fin-stat-in">
                <span class="fin-stat-label">الديون لنا</span>
                <strong>{{ number_format($debts['receivable'], 2) }} {{ $currency }}</strong>
                <span class="fin-stat-sub">مستحق من العملاء (فواتير مبيع دين)</span>
            </div>
            <div class="fin-stat fin-stat-net">
                <span class="fin-stat-label">قيمة المخزون</span>
                <strong>{{ number_format($summary['inventory']['total_value'], 2) }} {{ $currency }}</strong>
                <span class="fin-stat-sub">كمية × آخر سعر شراء</span>
            </div>
        </div>
    </section>

    <section class="inv-form-card mt-4">
        <div class="inv-section">
            <h2 class="inv-section-title">كيف يُحسب الربح؟</h2>
            <div class="profit-breakdown">
                <div class="profit-breakdown-row profit-breakdown-in">
                    <span>المبيعات (POS + فواتير مبيع)</span>
                    <strong>+ {{ number_format($sales['total'], 2) }} {{ $currency }}</strong>
                </div>
                <div class="profit-breakdown-row profit-breakdown-in">
                    <span>قبض خارجي</span>
                    <strong>+ {{ number_format($summary['other_income']['external_receipts'], 2) }} {{ $currency }}</strong>
                </div>
                <div class="profit-breakdown-row profit-breakdown-out">
                    <span>المشتريات</span>
                    <strong>− {{ number_format($summary['purchases'], 2) }} {{ $currency }}</strong>
                </div>
                <div class="profit-breakdown-row profit-breakdown-out">
                    <span>المصاريف</span>
                    <strong>− {{ number_format($summary['expenses'], 2) }} {{ $currency }}</strong>
                </div>
                <div class="profit-breakdown-row profit-breakdown-out">
                    <span>الرواتب والسلف</span>
                    <strong>− {{ number_format($payroll['total_out'], 2) }} {{ $currency }}</strong>
                </div>
                <div @class(['profit-breakdown-row', 'profit-breakdown-total', 'profit-breakdown-in' => $isPositive, 'profit-breakdown-out' => ! $isPositive])>
                    <span>صافي الربح التشغيلي</span>
                    <strong>= {{ number_format($profit['operating'], 2) }} {{ $currency }}</strong>
                </div>
                <div class="profit-breakdown-row profit-breakdown-withdrawal">
                    <span>مسحوبات الشريك (سحب من الربح)</span>
                    <strong>− {{ number_format($ownerWithdrawals, 2) }} {{ $currency }}</strong>
                </div>
                <div @class(['profit-breakdown-row', 'profit-breakdown-total', 'profit-breakdown-in' => $isPositiveAfter, 'profit-breakdown-out' => ! $isPositiveAfter])>
                    <span>الربح المتبقي</span>
                    <strong>= {{ number_format($profit['net_after_withdrawals'], 2) }} {{ $currency }}</strong>
                </div>
            </div>
            <p class="profit-note">
                مسحوبات الشريك لا تُحسب ضمن المصاريف — تُخصم من الربح فقط. الديون والمخزون أرقام مرجعية للوضع الحالي.
            </p>
        </div>
    </section>
</div>
