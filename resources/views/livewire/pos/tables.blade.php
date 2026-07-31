<div class="pos-shell pos-page">
    <x-pos.header :open-tables-count="$openTablesCount" />

    @if ($successMessage)
        <div class="pos-toast pos-toast-success">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                {{ $successMessage }}
            </span>
            <button type="button" wire:click="$set('successMessage', null)" class="pos-toast-close">×</button>
        </div>
    @endif

    @error('close')
        <div class="pos-toast pos-toast-error">{{ $message }}</div>
    @enderror

    <main class="pos-page-main">
        <div class="pos-page-head">
            <div>
                <h1 class="pos-page-title">الطاولات المفتوحة</h1>
                <p class="pos-page-sub">اختر طاولة لإغلاقها وتسجيل المبلغ المقبوض</p>
            </div>
            <a href="{{ route('pos.cashier') }}" class="pos-checkout-btn pos-page-cta">العودة للكاشير</a>
        </div>

        @if ($closingTableNumber && $closingOrder)
            <section class="pos-page-card pos-close-table-form max-w-lg">
                <h3 class="pos-page-title text-base">إغلاق طاولة {{ $closingTableNumber }}</h3>
                <p class="pos-close-table-total">المطلوب: <strong>{{ number_format($closingOrder->total, 2) }} {{ $currency }}</strong></p>
                <label class="pos-close-label">المبلغ المقبوض من الزبون</label>
                <input type="number" step="0.01" min="0" wire:model="closingPaidAmount" class="pos-input">
                <div class="pos-close-actions">
                    <button type="button" wire:click="closeTable" wire:loading.attr="disabled" class="pos-checkout-btn">تأكيد الإغلاق</button>
                    <button type="button" wire:click="cancelCloseTable" class="pos-clear-btn">إلغاء</button>
                </div>
            </section>
        @else
            <div class="pos-open-tables-list pos-page-card">
                @forelse ($openTables as $tableOrder)
                    <button type="button" wire:click="openCloseTable({{ $tableOrder->table_number }})" class="pos-open-table-card" wire:key="open-table-{{ $tableOrder->id }}">
                        <span class="pos-open-table-num">طاولة {{ $tableOrder->table_number }}</span>
                        <span class="pos-open-table-total">{{ number_format($tableOrder->total, 2) }} {{ $currency }}</span>
                        <span class="pos-open-table-meta">{{ $tableOrder->items_count }} صنف · طلب #{{ $tableOrder->id }} · {{ $tableOrder->source->label() }}</span>
                    </button>
                @empty
                    <p class="pos-tables-empty py-12">لا توجد طاولات مفتوحة — أرسل طلباً من الكاشير.</p>
                @endforelse
            </div>
        @endif
    </main>
</div>
