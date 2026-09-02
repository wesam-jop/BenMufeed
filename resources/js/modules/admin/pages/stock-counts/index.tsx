import { Head, Link } from '@inertiajs/react';
import { ClipboardList, Eye, Pencil } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatDate, stockCountStatusLabel, stockCountStatusVariant } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type StockCountRow = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    warehouse_name: string;
    items_count: number;
    approver_name: string | null;
    counted_at: string | null;
    created_at: string | null;
    can_edit: boolean;
};

type Props = {
    stockCounts: Paginated<StockCountRow>;
};

export default function StockCountsIndex({ stockCounts }: Props) {
    const { t } = useI18n();
    const items = stockCounts.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.stock_counts')} />
            <PageHeader
                title={t('admin.stock_counts.title')}
                description={t('admin.stock_counts.description')}
                createHref="/admin/stock-counts/create"
                createLabel={t('admin.stock_counts.create')}
            />

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <ClipboardList className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.stock_counts.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.stock_counts.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/stock-counts/create">
                                {t('admin.stock_counts.empty_cta')}
                            </Link>
                        </Button>
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
                                            {t('admin.stock_counts.col_reference')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.stock_counts.col_warehouse')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.stock_counts.col_materials')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.stock_counts.col_by')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{item.reference}</td>
                                            <td className="px-4 py-3">{item.warehouse_name}</td>
                                            <td className="px-4 py-3">
                                                {t('admin.stock_counts.materials_count').replace(
                                                    ':count',
                                                    String(item.items_count),
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={stockCountStatusVariant(item.status)}>
                                                    {stockCountStatusLabel(item.status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.counted_at
                                                    ? formatDate(item.counted_at)
                                                    : item.created_at
                                                      ? formatDate(item.created_at)
                                                      : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.approver_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/stock-counts/${item.id}`}>
                                                            {item.can_edit ? (
                                                                <>
                                                                    <Pencil className="size-4" />
                                                                    {t('admin.stock_counts.continue')}
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Eye className="size-4" />
                                                                    {t('common.view')}
                                                                </>
                                                            )}
                                                        </Link>
                                                    </Button>
                                                    {item.can_edit && (
                                                        <ResourceActions
                                                            deleteHref={`/admin/stock-counts/${item.id}`}
                                                            deletePermission="stock-counts.delete"
                                                        />
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={stockCounts} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
