import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import SalesRowActions from '@/components/admin/sales-row-actions';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatDate, statusLabel } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type TableSessionRow = {
    id: number;
    status: string;
    guest_count?: number | null;
    opened_at?: string | null;
    closed_at?: string | null;
    table_name?: string | null;
    table_zone?: string | null;
};

type Props = {
    tableSessions: Paginated<TableSessionRow>;
};

function statusVariant(status: string): 'default' | 'secondary' | 'outline' {
    return status === 'open' ? 'default' : 'secondary';
}

export default function TableSessionsIndex({ tableSessions }: Props) {
    const { t } = useI18n();
    const items = tableSessions.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.table_sessions')} />
            <PageHeader
                title={t('admin.nav.table_sessions')}
                description={t('admin.table_sessions.description')}
            />
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.table_sessions.table')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.table_sessions.zone')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.table_sessions.guests')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.table_sessions.opened_at')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.table_sessions.closed_at')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            {t('admin.table_sessions.empty')}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">
                                                {item.table_name ?? `#${item.id}`}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.table_zone ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.guest_count ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={statusVariant(item.status)}>
                                                    {statusLabel(item.status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.opened_at
                                                    ? formatDate(item.opened_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.closed_at
                                                    ? formatDate(item.closed_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <SalesRowActions
                                                    viewHref={`/admin/table-sessions/${item.id}`}
                                                    deleteHref={`/admin/table-sessions/${item.id}`}
                                                    viewPermission="table-sessions.view"
                                                    deletePermission="table-sessions.delete"
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={tableSessions} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
