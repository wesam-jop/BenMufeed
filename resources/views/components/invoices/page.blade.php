@props([
    'title',
    'icon' => 'document-text',
    'altLink' => null,
    'altLabel' => null,
    'recentInvoices',
    'currency',
    'customerOptions',
    'materialOptions',
    'paymentTypeOptions',
    'statusOptions',
    'invoiceType' => 'purchase',
    'linesTotal' => 0,
    'linesCount' => 0,
    'remainingAfterPayment' => 0,
    'panel' => 'create',
    'editingId' => null,
    'lines' => [],
    'paymentType' => 'cash',
    'savedInvoiceNumber' => null,
    'existingPaidAmount' => 0,
    'paymentAmount' => null,
    'invoiceDate' => '',
    'status' => 'confirmed',
    'customerId' => null,
    'notes' => '',
])

<div class="inv-page">
    <header class="inv-page-header">
        <div class="inv-page-brand">
            <span class="inv-page-icon"><x-ui.icon :name="$icon" class="h-6 w-6" /></span>
            <div>
                <p class="inv-page-title">بن مفيد</p>
                <p class="inv-page-sub">{{ $title }}</p>
            </div>
        </div>
        <div class="inv-page-actions">
            <div class="inv-tabs inv-tabs-light">
                <button type="button" wire:click="setPanel('create')" @class(['inv-tab', 'inv-tab-active' => $panel === 'create'])>
                    فاتورة جديدة
                </button>
                <button type="button" wire:click="setPanel('history')" @class(['inv-tab', 'inv-tab-active' => $panel === 'history'])>
                    السجل
                </button>
            </div>
            <a href="{{ url('/admin') }}" class="inv-nav-link">لوحة التحكم</a>
            @if ($altLink)
                <a href="{{ $altLink }}" class="inv-nav-link">{{ $altLabel }}</a>
            @endif
        </div>
    </header>

    @if ($savedInvoiceNumber)
        <div class="inv-alert inv-alert-success">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                تم حفظ الفاتورة {{ $savedInvoiceNumber }}
            </span>
            <button type="button" wire:click="$set('savedInvoiceNumber', null)" class="inv-alert-close">×</button>
        </div>
    @endif

    @error('save')
        <div class="inv-alert inv-alert-error">{{ $message }}</div>
    @enderror

    @if ($panel === 'history')
        {{ $history ?? '' }}
    @else
        <div class="inv-form-card">
            <div class="inv-form-head">
                <div>
                    <h1 class="inv-form-title">{{ $editingId ? 'تعديل فاتورة' : 'فاتورة جديدة' }}</h1>
                    <p class="inv-form-sub">أدخل بيانات الفاتورة وبنودها — يمكنك تعديل الكمية والسعر لكل مادة.</p>
                </div>
                @if ($editingId)
                    <span class="inv-form-badge">تعديل #{{ $editingId }}</span>
                @endif
            </div>

            <section class="inv-section">
                <h2 class="inv-section-title">بيانات الفاتورة</h2>
                <div class="inv-fields-grid">
                    <div class="inv-field">
                        <label class="inv-label">التاريخ</label>
                        <input type="date" wire:model="invoiceDate" class="inv-input">
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">الحالة</label>
                        <x-searchable-select wire:model.live="status" :options="$statusOptions" placeholder="اختر الحالة..." />
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">نوع الدفع</label>
                        <x-searchable-select wire:model.live="paymentType" :options="$paymentTypeOptions" placeholder="نقدي أو دين..." />
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">
                            العميل
                            @if($paymentType === 'credit')<span class="inv-required">*</span>@endif
                        </label>
                        <x-searchable-select
                            wire:model="customerId"
                            :options="$customerOptions"
                            placeholder="ابحث عن عميل..."
                            :required="$paymentType === 'credit'"
                        />
                        @error('customerId')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="inv-section">
                <div class="inv-section-head">
                    <h2 class="inv-section-title">بنود الفاتورة</h2>
                    <button type="button" wire:click="addLine" class="inv-btn-add">+ إضافة بند</button>
                </div>

                <div class="inv-lines-wrap">
                    <div class="inv-lines-scroll">
                    <table class="inv-lines-table">
                        <thead>
                            <tr>
                                <th class="inv-col-num">#</th>
                                <th class="inv-col-material">المادة</th>
                                <th class="inv-col-unit">الوحدة</th>
                                <th class="inv-col-qty">الكمية</th>
                                <th class="inv-col-price">سعر الوحدة</th>
                                <th class="inv-col-total">الإجمالي</th>
                                <th class="inv-col-action"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines ?? [] as $index => $line)
                                <tr wire:key="{{ $line['key'] }}">
                                    <td class="inv-col-num">{{ $index + 1 }}</td>
                                    <td class="inv-col-material">
                                        <x-searchable-select
                                            wire:model.live="lines.{{ $index }}.material_id"
                                            :options="$materialOptions"
                                            placeholder="ابحث عن مادة..."
                                            search-placeholder="اسم، SKU، سعر..."
                                            class="inv-select-material"
                                        />
                                        @error('lines.'.$index.'.material_id')
                                            <p class="inv-field-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="inv-col-unit">
                                        <span class="inv-unit-badge">{{ $line['unit'] ?: '—' }}</span>
                                    </td>
                                    <td class="inv-col-qty">
                                        <input
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            wire:model.blur="lines.{{ $index }}.quantity"
                                            class="inv-input inv-input-num"
                                        >
                                    </td>
                                    <td class="inv-col-price">
                                        <div class="inv-input-suffix">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                wire:model.blur="lines.{{ $index }}.unit_price"
                                                class="inv-input inv-input-num"
                                            >
                                            <span>{{ $currency }}</span>
                                        </div>
                                    </td>
                                    <td class="inv-col-total">
                                        <strong>{{ number_format($line['line_total'], 2) }}</strong>
                                        <span class="inv-currency">{{ $currency }}</span>
                                    </td>
                                    <td class="inv-col-action">
                                        <button
                                            type="button"
                                            wire:click="removeLine({{ $index }})"
                                            class="inv-btn-remove"
                                            aria-label="حذف البند"
                                            @disabled(count($lines ?? []) === 1)
                                        >×</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>

            <section class="inv-section inv-footer-grid">
                <div class="inv-notes-block">
                    <label class="inv-label">ملاحظات</label>
                    <textarea wire:model="notes" class="inv-input inv-textarea" rows="4" placeholder="ملاحظات إضافية (اختياري)"></textarea>
                </div>

                <div class="inv-totals-block">
                    <div class="inv-totals-card">
                        <div class="inv-total-row">
                            <span>عدد البنود</span>
                            <strong>{{ $linesCount }}</strong>
                        </div>
                        <div class="inv-total-row inv-total-main">
                            <span>الإجمالي</span>
                            <strong>{{ number_format($linesTotal, 2) }} {{ $currency }}</strong>
                        </div>

                        @if ($existingPaidAmount > 0)
                            <div class="inv-total-row inv-total-paid">
                                <span>مدفوع سابقاً</span>
                                <strong>{{ number_format($existingPaidAmount, 2) }} {{ $currency }}</strong>
                            </div>
                        @endif

                        @if ($paymentType === 'credit')
                            <div class="inv-field inv-field-compact">
                                <label class="inv-label">المبلغ المدفوع (دفعة)</label>
                                <input type="number" step="0.01" min="0" wire:model.live="paymentAmount" class="inv-input" placeholder="0.00">
                                @error('paymentAmount')<p class="inv-field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="inv-total-row inv-total-remaining">
                                <span>المتبقي</span>
                                <strong>{{ number_format($remainingAfterPayment, 2) }} {{ $currency }}</strong>
                            </div>
                        @else
                            <div class="inv-total-row inv-total-paid">
                                <span>المدفوع (نقدي)</span>
                                <strong>{{ number_format($linesTotal, 2) }} {{ $currency }}</strong>
                            </div>
                        @endif
                    </div>

                    <div class="inv-form-actions">
                        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="inv-btn-primary">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'حفظ التعديلات' : 'حفظ الفاتورة' }}</span>
                            <span wire:loading wire:target="save">جاري الحفظ...</span>
                        </button>
                        @if ($editingId || collect($lines ?? [])->contains(fn ($l) => $l['material_id']))
                            <button type="button" wire:click="newInvoice" class="inv-btn-ghost">فاتورة جديدة</button>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    @endif
</div>
