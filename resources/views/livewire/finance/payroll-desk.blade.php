<div class="inv-page">
    <header class="inv-page-header">
        <div class="inv-page-brand">
            <span class="inv-page-icon"><x-ui.icon name="users" class="h-6 w-6" /></span>
            <div>
                <p class="inv-page-title">بن مفيد</p>
                <p class="inv-page-sub">الرواتب — مدين ودائن</p>
            </div>
        </div>
        <div class="inv-page-actions">
            <div class="inv-tabs inv-tabs-light">
                <button type="button" wire:click="setPanel('accrual')" @class(['inv-tab', 'inv-tab-active' => $panel === 'accrual'])>
                    استحقاق شهري
                </button>
                <button type="button" wire:click="setPanel('payment')" @class(['inv-tab', 'inv-tab-active' => $panel === 'payment'])>
                    دفعة راتب
                </button>
                <button type="button" wire:click="setPanel('ledger')" @class(['inv-tab', 'inv-tab-active' => $panel === 'ledger'])>
                    كشف الحساب
                </button>
            </div>
            <a href="{{ url('/admin/employees') }}" class="inv-nav-link">العمال</a>
            <a href="{{ url('/admin') }}" class="inv-nav-link">لوحة التحكم</a>
        </div>
    </header>

    @if ($savedReference)
        <div class="inv-alert inv-alert-success">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                {{ $savedReference }}
            </span>
            <button type="button" wire:click="$set('savedReference', null)" class="inv-alert-close">×</button>
        </div>
    @endif

    @error('accrual')
        <div class="inv-alert inv-alert-error">{{ $message }}</div>
    @enderror
    @error('payment')
        <div class="inv-alert inv-alert-error">{{ $message }}</div>
    @enderror
    @error('ledger')
        <div class="inv-alert inv-alert-error">{{ $message }}</div>
    @enderror

    @if ($panel === 'ledger')
        <section class="inv-history">
            <div class="fin-stats">
                @foreach ($employeeSummaries as $summary)
                    <div class="fin-stat @if($summary['balance'] > 0) fin-stat-net @else fin-stat-out @endif" wire:key="emp-sum-{{ $summary['employee']->id }}">
                        <span class="fin-stat-label">{{ $summary['employee']->name }}</span>
                        <strong>{{ number_format($summary['balance'], 2) }} {{ $currency }}</strong>
                        <span class="fin-stat-sub">راتب: {{ number_format($summary['employee']->monthly_salary, 2) }} {{ $currency }}</span>
                    </div>
                @endforeach
            </div>

            <div class="inv-history-head">
                <h2>كشف حساب العمال</h2>
                <div class="fin-filter">
                    <x-searchable-select
                        wire:model.live="ledgerEmployeeId"
                        :options="array_merge([['value' => '', 'label' => 'كل العمال', 'search' => 'الكل']], collect($employeeOptions)->map(fn ($o) => array_merge($o, ['value' => (string) $o['value']]))->all())"
                        placeholder="فلتر حسب العامل..."
                    />
                </div>
            </div>

            @if ($ledgerEmployeeId && count($ledgerRows) > 0)
                <div class="inv-table-wrap mb-4">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>الشهر</th>
                                <th>المبلغ</th>
                                <th>الرصيد</th>
                                <th>ملاحظات</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ledgerRows as $row)
                                @php $entry = $row['entry']; @endphp
                                <tr wire:key="ledger-row-{{ $entry->id }}">
                                    <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                                    <td>
                                        <span @class([
                                            'inv-badge',
                                            'inv-badge-success' => $entry->type->isCredit(),
                                            'inv-badge-danger' => $entry->type->isDebit(),
                                        ])>
                                            {{ $entry->type->icon() }} {{ $entry->type->shortLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $entry->periodLabel() ?? '—' }}</td>
                                    <td @class(['fin-amount-in' => $entry->type->isCredit(), 'fin-amount-out' => $entry->type->isDebit()])>
                                        {{ $entry->type->icon() }}{{ number_format($entry->amount, 2) }} {{ $currency }}
                                    </td>
                                    <td><strong>{{ number_format($row['balance'], 2) }} {{ $currency }}</strong></td>
                                    <td class="fin-details-cell">{{ $entry->notes ?: '—' }}</td>
                                    <td class="inv-row-actions">
                                        @if ($entry->type !== \App\Enums\EmployeeLedgerEntryType::Advance)
                                            <button type="button" wire:click="deleteEntry({{ $entry->id }})" wire:confirm="حذف هذا القيد؟" class="inv-btn-danger">حذف</button>
                                        @else
                                            <span class="text-xs text-gray-500">من الحركات المالية</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="inv-table-wrap">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>العامل</th>
                            <th>النوع</th>
                            <th>الشهر</th>
                            <th>المبلغ</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ledgerEntries as $entry)
                            <tr wire:key="ledger-{{ $entry->id }}">
                                <td>{{ $entry->id }}</td>
                                <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                                <td>{{ $entry->employee?->name ?? '—' }}</td>
                                <td>
                                    <span @class([
                                        'inv-badge',
                                        'inv-badge-success' => $entry->type->isCredit(),
                                        'inv-badge-danger' => $entry->type->isDebit(),
                                    ])>
                                        {{ $entry->type->icon() }} {{ $entry->type->shortLabel() }}
                                    </span>
                                </td>
                                <td>{{ $entry->periodLabel() ?? '—' }}</td>
                                <td @class(['fin-amount-in' => $entry->type->isCredit(), 'fin-amount-out' => $entry->type->isDebit()])>
                                    {{ $entry->type->icon() }}{{ number_format($entry->amount, 2) }} {{ $currency }}
                                </td>
                                <td class="fin-details-cell">{{ $entry->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="inv-empty-cell">لا توجد قيود بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @elseif ($panel === 'payment')
        <div class="inv-form-card">
            <div class="inv-form-head">
                <div>
                    <h1 class="inv-form-title">دفعة راتب (مدين)</h1>
                    <p class="inv-form-sub">صرف راتب أو جزء من المستحق للعامل — يُخصم من رصيده ويُسجّل كمصروف في الصندوق.</p>
                </div>
            </div>

            <section class="inv-section">
                <div class="inv-fields-grid fin-fields-grid">
                    <div class="inv-field inv-field-span-full">
                        <label class="inv-label">العامل <span class="inv-required">*</span></label>
                        <x-searchable-select wire:model.live="paymentEmployeeId" :options="$employeeOptions" placeholder="ابحث عن العامل..." :required="true" />
                        @error('paymentEmployeeId')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>

                    @if ($selectedEmployee)
                        <div class="inv-field inv-field-span-full">
                            <div class="fin-preview fin-preview-in">
                                <span>المتبقي للعامل {{ $selectedEmployee->name }}</span>
                                <strong>{{ number_format($selectedEmployee->balance(), 2) }} {{ $currency }}</strong>
                            </div>
                        </div>
                    @endif

                    <div class="inv-field">
                        <label class="inv-label">تاريخ الدفع</label>
                        <input type="date" wire:model="paymentDate" class="inv-input">
                        @error('paymentDate')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">المبلغ</label>
                        <div class="inv-input-suffix">
                            <input type="number" step="0.01" min="0.01" wire:model="paymentAmount" class="inv-input" placeholder="0.00">
                            <span>{{ $currency }}</span>
                        </div>
                        @error('paymentAmount')<p class="inv-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="inv-field inv-field-span-full">
                        <label class="inv-label">ملاحظات</label>
                        <textarea wire:model="paymentNotes" class="inv-input inv-textarea" rows="3" placeholder="اختياري..."></textarea>
                    </div>
                </div>

                <div class="inv-form-actions fin-form-actions">
                    <button type="button" wire:click="paySalary" wire:loading.attr="disabled" wire:target="paySalary" class="inv-btn-primary">
                        <span wire:loading.remove wire:target="paySalary">تسجيل دفعة الراتب</span>
                        <span wire:loading wire:target="paySalary">جاري الحفظ...</span>
                    </button>
                </div>
            </section>
        </div>
    @else
        <div class="inv-form-card">
            <div class="inv-form-head">
                <div>
                    <h1 class="inv-form-title">استحقاق راتب شهري (دائن)</h1>
                    <p class="inv-form-sub">سجّل راتب كل عامل نشط للشهر المحدد. المتبقي ينتقل تلقائياً للشهر التالي.</p>
                </div>
            </div>

            <section class="inv-section">
                <h2 class="inv-section-title">الشهر</h2>
                <div class="inv-fields-grid fin-fields-grid">
                    <div class="inv-field">
                        <label class="inv-label">السنة</label>
                        <input type="number" wire:model="accrualYear" min="2020" max="2100" class="inv-input">
                    </div>
                    <div class="inv-field">
                        <label class="inv-label">الشهر</label>
                        <select wire:model="accrualMonth" class="inv-input">
                            @foreach ($monthOptions as $month)
                                <option value="{{ $month['value'] }}">{{ $month['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="inv-section">
                <h2 class="inv-section-title">معاينة العمال</h2>
                <div class="inv-table-wrap">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>العامل</th>
                                <th>الراتب الشهري</th>
                                <th>المتبقي حالياً</th>
                                <th>حالة الشهر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accrualPreview as $row)
                                <tr wire:key="accrual-{{ $row['employee']->id }}">
                                    <td>{{ $row['employee']->name }}</td>
                                    <td>{{ number_format($row['employee']->monthly_salary, 2) }} {{ $currency }}</td>
                                    <td>{{ number_format($row['employee']->balance(), 2) }} {{ $currency }}</td>
                                    <td>
                                        @if ((float) $row['employee']->monthly_salary <= 0)
                                            <span class="inv-badge">بدون راتب</span>
                                        @elseif ($row['accrued'])
                                            <span class="inv-badge inv-badge-success inline-flex items-center gap-1">
                                                <x-ui.icon name="check" class="h-3.5 w-3.5" />
                                                مسجّل
                                            </span>
                                        @else
                                            <span class="inv-badge inv-badge-danger">غير مسجّل</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="inv-empty-cell">لا يوجد عمال نشطون — <a href="{{ url('/admin/employees/create') }}" class="underline">أضف عاملاً</a>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="inv-form-actions fin-form-actions">
                    <button type="button" wire:click="accrueMonth" wire:loading.attr="disabled" wire:target="accrueMonth" class="inv-btn-primary">
                        <span wire:loading.remove wire:target="accrueMonth">تسجيل استحقاق الشهر</span>
                        <span wire:loading wire:target="accrueMonth">جاري التسجيل...</span>
                    </button>
                </div>
            </section>
        </div>
    @endif
</div>
