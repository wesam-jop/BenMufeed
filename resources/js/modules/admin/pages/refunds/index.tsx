import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency, formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type Item = Record<string, unknown> & { id: number };

type Props = {
    refund: Paginated<Item>;
};

export default function RefundsIndex({ refund }: Props) {
    const { t } = useI18n();
    const items = refund.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.refunds')} />
            <PageHeader
                title={t('admin.nav.refunds')}
                description={t('admin.refunds.description')}
                createHref="/admin/refunds/create"
                createLabel={t('admin.refunds.create')}
            />
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">{t('common.amount')}</th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.refunds.reason')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            {t('admin.refunds.empty')}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                {formatCurrency(Number(item.amount ?? 0))}
                                            </td>
                                            <td className="px-4 py-3">
                                                {String(item.reason ?? '—')}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.created_at
                                                    ? formatDate(String(item.created_at))
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <ResourceActions
                                                    editHref={`/admin/refunds/${item.id}/edit`}
                                                    deleteHref={`/admin/refunds/${item.id}`}
                                                    editPermission="refunds.update"
                                                    deletePermission="refunds.delete"
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={refund} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
