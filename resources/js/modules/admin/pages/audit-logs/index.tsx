import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Eye, Filter, RotateCcw, User, Zap } from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/hooks/use-i18n';
import { auditEventVariant, formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type AuditLogRow = {
    id: number;
    event: string;
    event_label: string;
    auditable_type: string | null;
    auditable_type_label: string;
    auditable_id: number | null;
    user_id: number | null;
    user_name: string | null;
    ip: string | null;
    created_at: string | null;
    changes_count: number;
};

type EventRow = {
    event: string;
    event_label: string;
    count: number;
};

type Summary = {
    logs_count: number;
    users_count: number;
    events_count: number;
    today_count: number;
};

type FilterOption = { value: string; label: string };
type UserOption = { id: number; name: string };

type Filters = {
    from: string;
    to: string;
    event: string;
    user_id: string;
    search: string;
};

type Props = {
    logs: Paginated<AuditLogRow>;
    summary: Summary;
    eventBreakdown: EventRow[];
    filters: Filters;
    filterOptions: {
        events: FilterOption[];
        users: UserOption[];
    };
};

export default function AuditLogsIndex({
    logs,
    summary,
    eventBreakdown,
    filters,
    filterOptions,
}: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);
    const rows = logs.data ?? [];

    const updateFilter = (key: keyof Filters, value: string) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const applyFilters = () => {
        router.get('/admin/audit-logs', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        const reset: Filters = {
            from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
            to: new Date().toISOString().slice(0, 10),
            event: '',
            user_id: '',
            search: '',
        };
        setLocalFilters(reset);
        router.get('/admin/audit-logs', reset, { preserveState: true });
    };

    return (
        <>
            <Head title={t('admin.audit.title')} />
            <PageHeader
                title={t('admin.audit.title')}
                description={t('admin.audit.description')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.audit.stat_logs')}
                    value={summary.logs_count}
                    format="number"
                    icon={ClipboardList}
                    description={t('admin.audit.stat_today_desc').replace(
                        ':count',
                        String(summary.today_count),
                    )}
                />
                <StatCard
                    title={t('admin.audit.stat_users')}
                    value={summary.users_count}
                    format="number"
                    icon={User}
                />
                <StatCard
                    title={t('admin.audit.stat_events')}
                    value={summary.events_count}
                    format="number"
                    icon={Zap}
                />
            </div>

            {eventBreakdown.length > 0 && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-base">{t('admin.audit.event_breakdown')}</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {eventBreakdown.map((row) => (
                            <Button
                                key={row.event}
                                type="button"
                                size="sm"
                                variant={filters.event === row.event ? 'default' : 'outline'}
                                onClick={() => {
                                    const next = { ...localFilters, event: row.event };
                                    setLocalFilters(next);
                                    router.get('/admin/audit-logs', next, { preserveState: true });
                                }}
                            >
                                {row.event_label}
                                <Badge variant="secondary" className="ms-2">
                                    {row.count}
                                </Badge>
                            </Button>
                        ))}
                    </CardContent>
                </Card>
            )}

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('common.filters')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div className="grid gap-2">
                            <Label htmlFor="from">{t('common.from_date')}</Label>
                            <Input
                                id="from"
                                type="date"
                                value={localFilters.from}
                                onChange={(e) => updateFilter('from', e.target.value)}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="to">{t('common.to_date')}</Label>
                            <Input
                                id="to"
                                type="date"
                                value={localFilters.to}
                                onChange={(e) => updateFilter('to', e.target.value)}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="event">{t('admin.audit.event')}</Label>
                            <Select
                                value={localFilters.event || '__all__'}
                                onValueChange={(value) =>
                                    updateFilter('event', value === '__all__' ? '' : value)
                                }
                            >
                                <SelectTrigger id="event" className="h-11 w-full">
                                    <SelectValue placeholder={t('admin.audit.all_events')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">{t('admin.audit.all_events')}</SelectItem>
                                    {filterOptions.events.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="user_id">{t('admin.audit.user')}</Label>
                            <Select
                                value={localFilters.user_id || '__all__'}
                                onValueChange={(value) =>
                                    updateFilter('user_id', value === '__all__' ? '' : value)
                                }
                            >
                                <SelectTrigger id="user_id" className="h-11 w-full">
                                    <SelectValue placeholder={t('admin.audit.all_users')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">{t('admin.audit.all_users')}</SelectItem>
                                    {filterOptions.users.map((user) => (
                                        <SelectItem key={user.id} value={String(user.id)}>
                                            {user.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2 sm:col-span-2 xl:col-span-4">
                            <Label htmlFor="search">{t('common.search')}</Label>
                            <Input
                                id="search"
                                type="search"
                                value={localFilters.search}
                                onChange={(e) => updateFilter('search', e.target.value)}
                                placeholder={t('admin.audit.search_placeholder')}
                            />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button onClick={applyFilters}>{t('common.apply_filters')}</Button>
                        <Button variant="outline" onClick={resetFilters}>
                            <RotateCcw className="size-4" />
                            {t('common.reset')}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {rows.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <ClipboardList className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">{t('admin.audit.empty')}</p>
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.audit.col_event')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.audit.col_record')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.audit.col_user')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.audit.col_ip')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row) => (
                                        <tr key={row.id} className="border-b hover:bg-muted/20">
                                            <td className="px-4 py-3">
                                                <Badge variant={auditEventVariant(row.event)}>
                                                    {row.event_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <p>{row.auditable_type_label}</p>
                                                {row.auditable_id && (
                                                    <p className="text-xs text-muted-foreground">
                                                        #{row.auditable_id}
                                                        {row.changes_count > 0 &&
                                                            ` · ${t('admin.audit.fields_count').replace(':count', String(row.changes_count))}`}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.user_id ? (
                                                    <Link
                                                        href={`/admin/users/${row.user_id}`}
                                                        className="hover:underline"
                                                    >
                                                        {row.user_name ?? '—'}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                                                {row.ip ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {row.created_at
                                                    ? formatDate(row.created_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/admin/audit-logs/${row.id}`}>
                                                        <Eye className="size-4" />
                                                        {t('common.view')}
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={logs} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
