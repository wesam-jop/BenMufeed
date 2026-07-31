<div class="inv-page">
    <header class="inv-page-header">
        <div class="inv-page-brand">
            <span class="inv-page-icon"><x-ui.icon name="banknotes" class="h-6 w-6" /></span>
            <div>
                <p class="inv-page-title">بن مفيد</p>
                <p class="inv-page-sub">الحركات المالية</p>
            </div>
        </div>
        <div class="inv-page-actions">
            <div class="inv-tabs inv-tabs-light">
                <button type="button" wire:click="setPanel('create')" @class(['inv-tab', 'inv-tab-active' => $panel === 'create'])>
                    حركة جديدة
                </button>
                <button type="button" wire:click="setPanel('history')" @class(['inv-tab', 'inv-tab-active' => $panel === 'history'])>
                    السجل
                </button>
            </div>
            <a href="{{ url('/admin') }}" class="inv-nav-link">لوحة التحكم</a>
        </div>
    </header>

    @if ($savedReference)
        <div class="inv-alert inv-alert-success">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                تم تسجيل الحركة {{ $savedReference }}
            </span>
            <button type="button" wire:click="$set('savedReference', null)" class="inv-alert-close">×</button>
        </div>
    @endif

    @error('save')
        <div class="inv-alert inv-alert-error">{{ $message }}</div>
    @enderror

    @if ($panel === 'history')
        <section class="inv-history">
            <div class="fin-stats">
                <div class="fin-stat fin-stat-in">
                    <span class="fin-stat-label">إجمالي القبض</span>
                    <strong>{{ number_format($totalIncoming, 2) }} {{ $currency }}</strong>
                </div>
                <div class="fin-stat fin-stat-out">
                    <span class="fin-stat-label">صرف تشغيلي</span>
                    <strong>{{ number_format($totalOperatingOutgoing, 2) }} {{ $currency }}</strong>
                    <span class="fin-stat-sub">مصاريف · دفعات · سلف</span>
                </div>
                <div class="fin-stat fin-stat-withdrawal">
                    <span class="fin-stat-label">مسحوبات الشريك</span>
                    <strong>{{ number_format($totalOwnerWithdrawals, 2) }} {{ $currency }}</strong>
                    <span class="fin-stat-sub">سحب من الربح — ليس مصروفاً</span>
                </div>
                <div class="fin-stat fin-stat-net">
                    <span class="fin-stat-label">صافي الصندوق</span>
                    <strong>{{ number_format($netBalance, 2) }} {{ $currency }}</strong>
                </div>
            </div>

            <div class="inv-history-head">
                <h2>سجل الحركات المالية</h2>
                <div class="fin-filter">
                    <x-searchable-select
                        wire:model.live="filterType"
                        :options="array_merge([['value' => '', 'label' => 'كل الأنواع', 'search' => 'الكل']], $typeOptions)"
                        placeholder="فلتر حسب النوع..."
                    />
                </div>
            </div>

            <div class="inv-table-wrap">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>الوصف</th>
                            <th>العميل / العامل</th>
                            <th>المبلغ</th>
                            <th>التفاصيل</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr wire:key="fin-{{ $movement->id }}">
                                <td>{{ $movement->id }}</td>
                                <td>{{ $movement->movement_date->format('Y-m-d') }}</td>
                                <td>
                                    <span @class([
                                        'inv-badge',
                                        'inv-badge-success' => $movement->type->isIncoming(),
                                        'inv-badge-warning' => $movement->type->isOwnerWithdrawal(),
                                        'inv-badge-danger' => ! $movement->type->isIncoming() && ! $movement->type->isOwnerWithdrawal(),
                                    ])>
                                        {{ $movement->type->icon() }} {{ $movement->type->label() }}
                                    </span>
                                </td>
                                <td>{{ $movement->title }}</td>
                                <td>
                                    @if ($movement->employee)
                                        {{ $movement->employee->name }} <span class="text-xs opacity-70">(عامل)</span>
                                    @else
                                        {{ $movement->customer?->name ?? '—' }}
                                    @endif
                                </td>
                                <td @class([
                                    'fin-amount-in' => $movement->type->isIncoming(),
                                    'fin-amount-withdrawal' => $movement->type->isOwnerWithdrawal(),
                                    'fin-amount-out' => ! $movement->type->isIncoming() && ! $movement->type->isOwnerWithdrawal(),
                                ])>
                                    {{ $movement->type->isIncoming() ? '+' : '−' }}{{ number_format($movement->amount, 2) }} {{ $currency }}
                                </td>
                                <td class="fin-details-cell">{{ $movement->details ?: '—' }}</td>
                                <td class="inv-row-actions">
                                    <button type="button" wire:click="deleteMovement({{ $movement->id }})" wire:confirm="حذف هذه الحركة؟" class="inv-btn-danger">حذف</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="inv-empty-cell">لا توجد حركات مالية بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="inv-form-card">
            <div class="inv-form-head">
                <div>
                    <h1 class="inv-form-title">تسجيل حركة مالية</h1>
                    <p class="inv-form-sub">مصروف، دفعة، سلفة، مسحوبات الشريك، أو قبض خارجي.</p>
                </div>
            </div>

            <section class="inv-section">
                <h2 class="inv-section-title">نوع الحركة</h2>
                <div class="fin-type-cards">
                    @foreach ($typeOptions as $option)
                        <button
                            type="button"
                            wire:click="$set('type', '{{ $option['value'] }}')"
                            @class([
                                'fin-type-card',
                                'fin-type-card-active' => $type === $option['value'],
                                'fin-type-card-expense' => in_array($option['value'], ['expense', 'customer_payment', 'employee_advance']),
                                'fin-type-card-withdrawal' => $option['value'] === 'owner_withdrawal',
                                'fin-type-card-in' => $option['value'] === 'external_receipt',
                            ])
                        >
                            <span class="fin-type-card-icon">
                                @if ($option['value'] === 'expense') ↓
                                @elseif ($option['value'] === 'customer_payment') ↗
                                @elseif ($option['value'] === 'employee_advance') ↘
                                @elseif ($option['value'] === 'owner_withdrawal') ⇣
                                @else ↑
                                @endif
                            </span>
                            <span class="fin-type-card-label">{{ $option['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="inv-section">
                <h2 class="inv-section-title">بيانات الحركة</h2>
                <div class="inv-fields-grid fin-fields-grid">
                    <div class="inv-field">
                        <label class="inv-label">التاريخ</label>
                        <input type="date" wire:model="movementDate" class="inv-input">
                        @error('movementDate')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">المبلغ</label>
                        <div class="inv-input-suffix">
                            <input type="number" step="0.01" min="0.01" wire:model="amount" class="inv-input" placeholder="0.00">
                            <span>{{ $currency }}</span>
                        </div>
                        @error('amount')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="inv-field inv-field-span-2">
                        <label class="inv-label">الوصف المختصر</label>
                        <input type="text" wire:model="title" class="inv-input" placeholder="مثال: دفعة للمورد أحمد، فاتورة كهرباء، قبض إيجار...">
                        @error('title')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>

                    @if ($type === 'customer_payment')
                        <div class="inv-field inv-field-span-full">
                            <label class="inv-label">العميل (المستفيد) <span class="inv-required">*</span></label>
                            <x-searchable-select wire:model.live="customerId" :options="$customerOptions" placeholder="ابحث عن العميل الذي تدفع له..." :required="true" />
                            @error('customerId')<p class="inv-field-error">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    @if ($type === 'employee_advance')
                        <div class="inv-field inv-field-span-full">
                            <label class="inv-label">العامل <span class="inv-required">*</span></label>
                            <x-searchable-select wire:model.live="employeeId" :options="$employeeOptions" placeholder="ابحث عن العامل..." :required="true" />
                            @error('employeeId')<p class="inv-field-error">{{ $message }}</p>@enderror
                            <p class="inv-field-hint">تُسجَّل السلفة كمدين في حساب العامل وتُخصم من الصندوق.</p>
                        </div>
                    @endif

                    <div class="inv-field inv-field-span-full">
                        <label class="inv-label">التفاصيل</label>
                        <textarea wire:model="details" class="inv-input inv-textarea" rows="4" placeholder="تفاصيل إضافية: طريقة الدفع، ملاحظات، مرجع..."></textarea>
                        @error('details')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="inv-section">
                <div class="fin-preview @if($type === 'external_receipt') fin-preview-in @elseif($type === 'owner_withdrawal') fin-preview-withdrawal @else fin-preview-out @endif">
                    @if ($type === 'expense')
                        <span>مصروف — خروج من الصندوق (تشغيلي)</span>
                    @elseif ($type === 'customer_payment')
                        <span>دفعة إلى عميل — صرف (تسديد دين)</span>
                    @elseif ($type === 'employee_advance')
                        <span>سلفة لعامل — صرف (مدين في حساب العامل)</span>
                    @elseif ($type === 'owner_withdrawal')
                        <span>مسحوبات الشريك — سحب من الربح (ليس مصروفاً)</span>
                    @else
                        <span>قبض خارجي — دخل</span>
                    @endif
                    <strong>{{ $amount ? number_format((float) $amount, 2) : '0.00' }} {{ $currency }}</strong>
                </div>

                <div class="inv-form-actions fin-form-actions">
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="inv-btn-primary">
                        <span wire:loading.remove wire:target="save">تسجيل الحركة</span>
                        <span wire:loading wire:target="save">جاري الحفظ...</span>
                    </button>
                </div>
            </section>
        </div>
    @endif
</div>
