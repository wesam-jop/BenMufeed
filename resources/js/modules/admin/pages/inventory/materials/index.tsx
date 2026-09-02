import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Package, PackageX, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency, formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/admin';

type MaterialRow = {
    id: number;
    name: string;
    sku: string | null;
    description: string | null;
    cost_price: number;
    min_stock: number | null;
    stock_quantity: number;
    stock_value: number;
    stock_status: 'ok' | 'low' | 'out';
    is_active: boolean;
    unit: { name: string; abbreviation: string } | null;
};

type Summary = {
    materials_count: number;
    total_value: number;
    low_stock_count: number;
    out_of_stock_count: number;
    in_stock_count: number;
};

type Props = {
    materials: Paginated<MaterialRow>;
    summary: Summary;
};

export default function InventoryMaterialsIndex({ materials, summary }: Props) {
    const { t } = useI18n();
    const items = materials.data ?? [];
    const pageValue = items.reduce((sum, item) => sum + item.stock_value, 0);

    const stockStatusBadge = (status: MaterialRow['stock_status']) => {
        if (status === 'out') {
            return <Badge variant="destructive">{t('admin.materials.status_out')}</Badge>;
        }

        if (status === 'low') {
            return (
                <Badge className="border-amber-200 bg-amber-100 text-amber-900 hover:bg-amber-100">
                    {t('admin.materials.status_low')}
                </Badge>
            );
        }

        return (
            <Badge className="border-emerald-200 bg-emerald-100 text-emerald-800 hover:bg-emerald-100">
                {t('admin.materials.status_ok')}
            </Badge>
        );
    };

    return (
        <>
            <Head title={t('admin.nav.materials')} />
            <PageHeader
                title={t('admin.nav.materials')}
                description={t('admin.materials.description')}
                createHref="/admin/inventory/materials/create"
                createLabel={t('admin.materials.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.materials.total_value')}
                    value={summary.total_value}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.materials.total_value_desc')}
                />
                <StatCard
                    title={t('admin.materials.count')}
                    value={summary.materials_count}
                    format="number"
                    icon={Package}
                    description={`${summary.in_stock_count} ${t('admin.materials.in_stock')}`}
                />
                <StatCard
                    title={t('admin.materials.low_stock')}
                    value={summary.low_stock_count}
                    format="number"
                    icon={AlertTriangle}
                    description={t('admin.materials.low_stock_desc')}
                />
                <StatCard
                    title={t('admin.materials.out_of_stock')}
                    value={summary.out_of_stock_count}
                    format="number"
                    icon={PackageX}
                    description={t('admin.materials.out_of_stock_desc')}
                />
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.materials.col_material')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.materials.col_unit')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.materials.col_unit_cost')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.materials.col_balance')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.materials.col_alert')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.materials.col_value')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.materials.col_stock_status')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={9}
                                            className="px-4 py-10 text-center text-muted-foreground"
                                        >
                                            {t('admin.materials.empty')}{' '}
                                            <Link
                                                href="/admin/inventory/materials/create"
                                                className="font-medium text-primary underline-offset-4 hover:underline"
                                            >
                                                {t('admin.materials.empty_cta')}
                                            </Link>
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr
                                            key={item.id}
                                            className={cn(
                                                'border-b hover:bg-muted/30',
                                                item.stock_status === 'out' && 'bg-destructive/5',
                                                item.stock_status === 'low' && 'bg-amber-50/60',
                                            )}
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{item.name}</div>
                                                {item.sku && (
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.sku}
                                                    </div>
                                                )}
                                                {item.description && (
                                                    <div className="mt-0.5 max-w-xs truncate text-xs text-muted-foreground">
                                                        {item.description}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.unit?.name ?? '—'}
                                                {item.unit ? (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        ({item.unit.abbreviation})
                                                    </span>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.cost_price)}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatQuantity(item.stock_quantity)}
                                                {item.unit ? ` ${item.unit.abbreviation}` : ''}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.min_stock !== null
                                                    ? `${formatQuantity(item.min_stock)}${item.unit ? ` ${item.unit.abbreviation}` : ''}`
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 font-semibold">
                                                {formatCurrency(item.stock_value)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {stockStatusBadge(item.stock_status)}
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
                                                <ResourceActions
                                                    editHref={`/admin/inventory/materials/${item.id}/edit`}
                                                    editPermission="products.update"
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                            {items.length > 0 && (
                                <tfoot>
                                    <tr className="border-t bg-muted/40 font-semibold">
                                        <td colSpan={5} className="px-4 py-3 text-left">
                                            {t('admin.materials.page_total')}
                                        </td>
                                        <td className="px-4 py-3">{formatCurrency(pageValue)}</td>
                                        <td colSpan={3} className="px-4 py-3 text-muted-foreground">
                                            {t('admin.materials.grand_total')}{' '}
                                            <span className="text-foreground">
                                                {formatCurrency(summary.total_value)}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={materials} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
