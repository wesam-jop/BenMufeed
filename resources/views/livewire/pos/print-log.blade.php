<div class="pos-shell pos-page" wire:poll.60s.visible>
    <x-pos.header :open-tables-count="$openTablesCount" :failed-print-count="$failedCount" />

    @if ($flashMessage)
        <div class="pos-toast pos-toast-success">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                {{ $flashMessage }}
            </span>
            <button type="button" wire:click="$set('flashMessage', null)" class="pos-toast-close">×</button>
        </div>
    @endif

    <main class="pos-page-main">
        <div class="pos-page-head">
            <div>
                <h1 class="pos-page-title">سجل الطباعة</h1>
                <p class="pos-page-sub">تتبّع الطباعات التلقائية واليدوية — أعد تشغيل ما فشل بضغطة واحدة</p>
            </div>
            <div class="pos-print-log-filters">
                <select wire:model.live="filterStatus" class="pos-history-date">
                    <option value="all">كل الحالات</option>
                    <option value="failed">فاشلة فقط</option>
                    <option value="pending">بانتظار</option>
                    <option value="printing">جاري</option>
                    <option value="succeeded">ناجحة</option>
                </select>
                <input type="date" wire:model.live="filterDate" class="pos-history-date">
                @if ($failedCount > 0)
                    <button type="button" wire:click="retryAllFailed" class="pos-history-btn pos-print-retry-all">
                        ↻ إعادة الكل ({{ $failedCount }})
                    </button>
                @endif
            </div>
        </div>

        <div class="pos-history-table-wrap pos-page-card">
            <table class="pos-history-table pos-print-log-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الوقت</th>
                        <th>النوع</th>
                        <th>المرجع</th>
                        <th>الطابعة</th>
                        <th>الحالة</th>
                        <th>محاولات</th>
                        <th>الخطأ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $job)
                        <tr wire:key="print-job-{{ $job->id }}" @class(['pos-print-row-failed' => $job->status === \App\Enums\PrintJobStatus::Failed])>
                            <td>{{ $job->id }}</td>
                            <td>{{ $job->created_at->format('H:i:s') }}</td>
                            <td>{{ $job->kind->label() }}</td>
                            <td>{{ $job->referenceLabel() }}</td>
                            <td>{{ $job->printer_name ?: '—' }}</td>
                            <td>
                                <span @class([
                                    'pos-print-status',
                                    'pos-print-status-failed' => $job->status === \App\Enums\PrintJobStatus::Failed,
                                    'pos-print-status-ok' => $job->status === \App\Enums\PrintJobStatus::Succeeded,
                                    'pos-print-status-pending' => in_array($job->status, [\App\Enums\PrintJobStatus::Pending, \App\Enums\PrintJobStatus::Printing], true),
                                ])>
                                    {{ $job->status->label() }}
                                </span>
                            </td>
                            <td>{{ $job->attempts }}</td>
                            <td class="pos-print-error-cell">{{ $job->error_message ? \Illuminate\Support\Str::limit($job->error_message, 80) : '—' }}</td>
                            <td class="pos-history-actions">
                                @if ($job->status->canRetry())
                                    <button type="button" wire:click="retry({{ $job->id }})" class="pos-history-btn">
                                        ↻ إعادة
                                    </button>
                                @else
                                    <span class="pos-history-sub">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="pos-tables-empty">لا توجد سجلات طباعة في هذا التاريخ.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $jobs->links() }}
        </div>
    </main>
</div>
