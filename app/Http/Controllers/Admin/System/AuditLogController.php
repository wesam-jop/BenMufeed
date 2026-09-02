<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $from = $request->date('from')?->toDateString() ?? now()->subDays(30)->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $baseQuery = $this->auditLogsBaseQuery($request, $from, $to);

        $logs = (clone $baseQuery)
            ->with('user:id,name,username')
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $logs->through(fn (AuditLog $log) => $this->transformSummary($log));

        $allInPeriod = (clone $baseQuery)->get();

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
            'summary' => $this->buildSummary($allInPeriod),
            'eventBreakdown' => $this->eventBreakdown($allInPeriod),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'event' => $request->input('event', ''),
                'user_id' => $request->input('user_id', ''),
                'search' => $request->input('search', ''),
            ],
            'filterOptions' => [
                'events' => $this->eventFilterOptions(),
                'users' => $this->userFilterOptions(),
            ],
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $this->authorize('view', $auditLog);

        $auditLog->load('user:id,name,username,email');

        return Inertia::render('admin/audit-logs/show', [
            'auditLog' => $this->transformDetail($auditLog),
        ]);
    }

    private function auditLogsBaseQuery(Request $request, string $from, string $to): Builder
    {
        $query = AuditLog::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('ip', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('auditable_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /** @param  \Illuminate\Support\Collection<int, AuditLog>  $logs */
    private function buildSummary($logs): array
    {
        return [
            'logs_count' => $logs->count(),
            'users_count' => $logs->pluck('user_id')->filter()->unique()->count(),
            'events_count' => $logs->pluck('event')->unique()->count(),
            'today_count' => $logs->filter(
                fn (AuditLog $log) => $log->created_at?->isToday(),
            )->count(),
        ];
    }

    /** @param  \Illuminate\Support\Collection<int, AuditLog>  $logs */
    private function eventBreakdown($logs): array
    {
        return $logs
            ->groupBy('event')
            ->map(fn ($group, $event) => [
                'event' => $event,
                'event_label' => $this->eventLabel($event),
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(8)
            ->all();
    }

    /** @return array<string, mixed> */
    private function transformSummary(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'event' => $log->event,
            'event_label' => $this->eventLabel($log->event),
            'auditable_type' => $log->auditable_type,
            'auditable_type_label' => $this->auditableTypeLabel($log->auditable_type),
            'auditable_id' => $log->auditable_id,
            'user_id' => $log->user_id,
            'user_name' => $log->user?->name,
            'ip' => $log->ip,
            'created_at' => $log->created_at?->toIso8601String(),
            'changes_count' => $this->changesCount($log),
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(AuditLog $log): array
    {
        return [
            ...$this->transformSummary($log),
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'username' => $log->user->username,
                'email' => $log->user->email,
            ] : null,
            'old_values' => $log->old_values ?? [],
            'new_values' => $log->new_values ?? [],
            'changed_fields' => $this->changedFields($log),
            'user_agent' => $log->user_agent,
        ];
    }

    /** @return list<array{field: string, old: mixed, new: mixed}> */
    private function changedFields(AuditLog $log): array
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];
        $keys = collect(array_keys($old))->merge(array_keys($new))->unique()->values();

        return $keys
            ->map(fn (string $field) => [
                'field' => $field,
                'old' => $old[$field] ?? null,
                'new' => $new[$field] ?? null,
            ])
            ->values()
            ->all();
    }

    private function changesCount(AuditLog $log): int
    {
        return count($this->changedFields($log));
    }

    /** @return list<array{value: string, label: string}> */
    private function eventFilterOptions(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->map(fn (string $event) => [
                'value' => $event,
                'label' => $this->eventLabel($event),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, name: string}> */
    private function userFilterOptions(): array
    {
        $userIds = AuditLog::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'sale.completed' => 'إتمام بيع',
            'sale.voided' => 'إلغاء بيع',
            'refund.processed' => 'معالجة استرجاع',
            'purchase.received' => 'استلام شراء',
            'stock_count.approved' => 'اعتماد جرد',
            'production.completed' => 'إتمام إنتاج',
            default => str_replace('.', ' · ', $event),
        };
    }

    private function auditableTypeLabel(?string $type): string
    {
        if ($type === null) {
            return '—';
        }

        $short = class_basename($type);

        return match ($short) {
            'Invoice' => 'فاتورة',
            'Refund' => 'استرجاع',
            'PurchaseInvoice' => 'فاتورة شراء',
            'StockCount' => 'جرد مخزون',
            'ProductionOrder' => 'أمر إنتاج',
            'Order' => 'طلب',
            default => $short,
        };
    }
}
