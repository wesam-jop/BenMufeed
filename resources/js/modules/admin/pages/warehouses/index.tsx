import { Head, Link } from '@inertiajs/react';
import { Eye, Package, Warehouse as WarehouseIcon, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency, warehouseTypeLabel } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type WarehouseRow = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    is_active: boolean;
    materials_count: number;
    total_value: number;
};

type Summary = {
    warehouses_count: number;
    active_count: number;
    materials_count: number;
    total_value: number;
};

type Props = {
    warehouses: Paginated<WarehouseRow>;
    summary: Summary;
};

export default function WarehousesIndex({ warehouses, summary }: Props) {
    const { t } = useI18n();
    const items = warehouses.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.warehouses')} />
            <PageHeader
                title={t('admin.nav.warehouses')}
                description={t('admin.warehouses.description')}
                createHref="/admin/warehouses/create"
                createLabel={t('admin.warehouses.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.warehouses.count')}
                    value={summary.warehouses_count}
                    format="number"
                    icon={WarehouseIcon}
                    description={`${summary.active_count} ${t('common.active')}`}
                />
                <StatCard
                    title={t('admin.warehouses.stored_materials')}
                    value={summary.materials_count}
                    format="number"
                    icon={Package}
                    description={t('admin.warehouses.stored_materials_desc')}
                />
                <StatCard
                    title={t('admin.warehouses.stock_value')}
                    value={summary.total_value}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.warehouses.stock_value_desc')}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <WarehouseIcon className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.warehouses.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.warehouses.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/warehouses/create">
                                {t('admin.warehouses.create')}
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
                                        <th className="px-4 py-3 font-medium">{t('common.name')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.warehouses.col_type')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.warehouses.col_materials')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.warehouses.col_stock_value')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{item.name}</td>
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">
                                                    {item.type_label || warehouseTypeLabel(item.type)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {t('admin.warehouses.materials_count').replace(
                                                    ':count',
                                                    String(item.materials_count),
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.total_value)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={item.is_active ? 'default' : 'secondary'}
                                                >
                                                    {item.is_active
                                                        ? t('common.active')
                                                        : t('common.inactive')}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/warehouses/${item.id}`}>
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={`/admin/warehouses/${item.id}/edit`}
                                                        deleteHref={`/admin/warehouses/${item.id}`}
                                                        editPermission="warehouses.update"
                                                        deletePermission="warehouses.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={warehouses} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
