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

    @error('edit')
        <div class="pos-toast pos-toast-error">{{ $message }}</div>
    @enderror

    <main class="pos-page-main">
        <div class="pos-page-head">
            <div>
                <h1 class="pos-page-title">سجل الفواتير</h1>
                <p class="pos-page-sub">فواتير الكاشير — عرض وتعديل وحذف</p>
            </div>
            <input type="date" wire:model.live="historyDate" class="pos-history-date">
        </div>

        <div class="pos-history-table-wrap pos-page-card">
            <table class="pos-history-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الوقت</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>الإجمالي</th>
                        <th>مقبوض</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historyOrders as $order)
                        <tr wire:key="hist-{{ $order->id }}">
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->created_at->format('H:i') }}</td>
                            <td>
                                {{ $order->service_type?->label() ?? '—' }}
                                @if ($order->table_number)
                                    <span class="pos-history-sub">(طاولة {{ $order->table_number }})</span>
                                @endif
                            </td>
                            <td>{{ $order->status->label() }}</td>
                            <td>{{ number_format($order->total, 2) }} {{ $currency }}</td>
                            <td>
                                @if ($order->paid_amount !== null)
                                    {{ number_format($order->paid_amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="pos-history-actions">
                                <button
                                    type="button"
                                    class="pos-history-btn"
                                    onclick="window.benMufeedPrint?.cashierReceipt({{ $order->id }})"
                                >
                                    طباعة فاتورة
                                </button>
                                <a href="{{ route('pos.invoices.edit', $order) }}" class="pos-history-btn">تعديل</a>
                                <button type="button" wire:click="deleteOrder({{ $order->id }})" wire:confirm="حذف الفاتورة #{{ $order->id }}؟ سيتم استرجاع المخزون." class="pos-history-btn pos-history-btn-danger">حذف</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="pos-tables-empty">لا توجد فواتير في هذا التاريخ.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>
