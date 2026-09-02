import { Head, Link } from '@inertiajs/react';
import { ArrowLeftRight, Eye, Pencil, Truck } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatDate, stockTransferStatusLabel, stockTransferStatusVariant } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type TransferRow = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    from_warehouse_name: string;
    to_warehouse_name: string;
    items_count: number;
    user_name: string | null;
    created_at: string | null;
    can_edit: boolean;
};

type Props = {
    stockTransfers: Paginated<TransferRow>;
};

export default function StockTransfersIndex({ stockTransfers }: Props) {
    const { t } = useI18n();
    const items = stockTransfers.data ?? [];

    return (
        <>
            <Head title={t('admin.transfers.title')} />
            <PageHeader
                title={t('admin.transfers.title')}
                description={t('admin.transfers.description')}
                createHref="/admin/stock-transfers/create"
                createLabel={t('admin.transfers.create')}
            />

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <Truck className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.transfers.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.transfers.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/stock-transfers/create">
                                {t('admin.transfers.create')}
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
                                            {t('admin.transfers.col_reference')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.transfers.col_from')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.transfers.col_to')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.transfers.col_materials')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{item.reference}</td>
                                            <td className="px-4 py-3">{item.from_warehouse_name}</td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center gap-1">
                                                    <ArrowLeftRight className="size-3.5 text-muted-foreground" />
                                                    {item.to_warehouse_name}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {t('admin.transfers.materials_count').replace(
                                                    ':count',
                                                    String(item.items_count),
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={stockTransferStatusVariant(item.status)}>
                                                    {stockTransferStatusLabel(item.status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.created_at ? formatDate(item.created_at) : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/stock-transfers/${item.id}`}>
                                                            {item.can_edit ? (
                                                                <>
                                                                    <Pencil className="size-4" />
                                                                    {t('admin.transfers.continue')}
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
                                                            deleteHref={`/admin/stock-transfers/${item.id}`}
                                                            deletePermission="stock-transfers.delete"
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
                            <PaginationLinks paginator={stockTransfers} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
