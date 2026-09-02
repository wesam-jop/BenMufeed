import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    Filter,
    Layers,
    Package,
    RotateCcw,
    Warehouse,
    Wallet,
} from 'lucide-react';
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
import {
    formatCurrency,
    formatQuantity,
    stockStatusLabel,
    stockStatusVariant,
    warehouseTypeLabel,
} from '@/lib/format';
import type { Paginated } from '@/types/admin';

type BalanceRow = {
    id: number;
    product_id: number;
    warehouse_id: number;
    product_name: string;
    sku: string | null;
    unit: string | null;
    warehouse_name: string;
    warehouse_type: string | null;
    warehouse_type_label: string | null;
    quantity: number;
    avg_cost: number;
    total_value: number;
    min_stock: number;
    max_stock: number | null;
    is_active: boolean;
    stock_status: string;
    stock_status_label: string;
    updated_at: string | null;
};

type Summary = {
    materials_count: number;
    warehouses_count: number;
    total_quantity: number;
    total_value: number;
    low_stock_count: number;
    out_of_stock_count: number;
    negative_stock_count: number;
};

type WarehouseRow = {
    warehouse_id: number | null;
    warehouse_name: string;
    materials_count: number;
    total_quantity: number;
    total_value: number;
    low_stock_count: number;
};

type FilterOption = { value: string; label: string };
type WarehouseOption = { id: number; name: string };

type Filters = {
    warehouse_id: string;
    search: string;
    stock_status: string;
};

type Props = {
    balances: Paginated<BalanceRow>;
    summary: Summary;
    warehouseBreakdown: WarehouseRow[];
    filters: Filters;
    filterOptions: {
        warehouses: WarehouseOption[];
        stockStatuses: FilterOption[];
    };
};

export default function InventoryReport({
    balances,
    summary,
    warehouseBreakdown,
    filters,
    filterOptions,
}: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);
    const rows = balances.data ?? [];

    const updateFilter = (key: keyof Filters, value: string) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const applyFilters = () => {
        router.get('/admin/reports/inventory', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        const reset: Filters = {
            warehouse_id: '',
            search: '',
            stock_status: 'in_stock',
        };
        setLocalFilters(reset);
        router.get('/admin/reports/inventory', reset, { preserveState: true });
    };

    return (
        <>
            <Head title={t('admin.reports.inventory.title')} />
            <PageHeader
                title={t('admin.reports.inventory.title')}
                description={t('admin.reports.inventory.description')}
            />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('admin.reports.inventory.filters_title')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div className="grid gap-2">
                            <Label htmlFor="warehouse_id">
                                {t('admin.reports.inventory.warehouse')}
                            </Label>
                            <Select
                                value={localFilters.warehouse_id || '__all__'}
                                onValueChange={(v) =>
                                    updateFilter('warehouse_id', v === '__all__' ? '' : v)
                                }
                            >
                                <SelectTrigger id="warehouse_id" className="h-11 w-full">
                                    <SelectValue
                                        placeholder={t('admin.reports.inventory.all_warehouses')}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">
                                        {t('admin.reports.inventory.all_warehouses')}
                                    </SelectItem>
                                    {filterOptions.warehouses.map((warehouse) => (
                                        <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                            {warehouse.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="stock_status">
                                {t('admin.reports.inventory.stock_status')}
                            </Label>
                            <Select
                                value={localFilters.stock_status}
                                onValueChange={(value) => updateFilter('stock_status', value)}
                            >
                                <SelectTrigger id="stock_status" className="h-11 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.stockStatuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="search">{t('common.search')}</Label>
                            <Input
                                id="search"
                                type="search"
                                value={localFilters.search}
                                onChange={(e) => updateFilter('search', e.target.value)}
                                placeholder={t('admin.reports.inventory.search_placeholder')}
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

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.reports.inventory.stat_materials')}
                    value={summary.materials_count}
                    format="number"
                    icon={Package}
                    description={t('admin.reports.inventory.stat_warehouses_desc').replace(
                        ':count',
                        String(summary.warehouses_count),
                    )}
                />
                <StatCard
                    title={t('admin.reports.inventory.stat_total_qty')}
                    value={summary.total_quantity}
                    format="number"
                    icon={Layers}
                />
                <StatCard
                    title={t('admin.reports.inventory.stat_stock_value')}
                    value={summary.total_value}
                    format="currency"
                    icon={Wallet}
                />
                <StatCard
                    title={t('admin.reports.inventory.stat_alerts')}
                    value={
                        summary.low_stock_count +
                        summary.out_of_stock_count +
                        summary.negative_stock_count
                    }
                    format="number"
                    icon={AlertTriangle}
                    description={t('admin.reports.inventory.stat_alerts_desc')
                        .replace(':low', String(summary.low_stock_count))
                        .replace(':out', String(summary.out_of_stock_count))}
                />
            </div>

            {(summary.low_stock_count > 0 || summary.negative_stock_count > 0) && (
                <div className="mb-6 flex flex-wrap gap-2">
                    {summary.low_stock_count > 0 && (
                        <Badge variant="secondary">
                            {t('admin.reports.inventory.badge_low_stock').replace(
                                ':count',
                                String(summary.low_stock_count),
                            )}
                        </Badge>
                    )}
                    {summary.out_of_stock_count > 0 && (
                        <Badge variant="outline">
                            {t('admin.reports.inventory.badge_out').replace(
                                ':count',
                                String(summary.out_of_stock_count),
                            )}
                        </Badge>
                    )}
                    {summary.negative_stock_count > 0 && (
                        <Badge variant="destructive">
                            {t('admin.reports.inventory.badge_negative').replace(
                                ':count',
                                String(summary.negative_stock_count),
                            )}
                        </Badge>
                    )}
                </div>
            )}

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Warehouse className="size-4" />
                        {t('admin.reports.inventory.by_warehouse')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {warehouseBreakdown.length === 0 ? (
                        <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                            {t('admin.reports.inventory.empty_warehouses')}
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_warehouse')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_materials')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_qty')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_value')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_low')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {warehouseBreakdown.map((row) => (
                                        <tr
                                            key={row.warehouse_id ?? row.warehouse_name}
                                            className="border-b"
                                        >
                                            <td className="px-4 py-3">
                                                {row.warehouse_id ? (
                                                    <Link
                                                        href={`/admin/warehouses/${row.warehouse_id}`}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {row.warehouse_name}
                                                    </Link>
                                                ) : (
                                                    row.warehouse_name
                                                )}
                                            </td>
                                            <td className="px-4 py-3">{row.materials_count}</td>
                                            <td className="px-4 py-3">
                                                {formatQuantity(row.total_quantity)}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(row.total_value)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.low_stock_count > 0 ? (
                                                    <Badge variant="secondary">
                                                        {row.low_stock_count}
                                                    </Badge>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <CardTitle>{t('admin.reports.inventory.balance_details')}</CardTitle>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/inventory/materials">
                                {t('admin.reports.inventory.materials_link')}
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/warehouses">
                                {t('admin.reports.inventory.warehouses_link')}
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {rows.length === 0 ? (
                        <div className="flex flex-col items-center gap-4 py-16 text-center">
                            <Package className="size-10 text-muted-foreground" />
                            <p className="text-muted-foreground">
                                {t('admin.reports.inventory.empty')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_material')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_warehouse')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_qty')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_unit_cost')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_value')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_min')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.inventory.col_status')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row) => (
                                        <tr key={row.id} className="border-b hover:bg-muted/20">
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/admin/products/${row.product_id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.product_name}
                                                </Link>
                                                {row.sku && (
                                                    <p className="text-xs font-mono text-muted-foreground">
                                                        {row.sku}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/admin/warehouses/${row.warehouse_id}`}
                                                    className="hover:underline"
                                                >
                                                    {row.warehouse_name}
                                                </Link>
                                                {row.warehouse_type && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {row.warehouse_type_label ||
                                                            warehouseTypeLabel(row.warehouse_type)}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatQuantity(row.quantity)}
                                                {row.unit && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {' '}
                                                        {row.unit}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(row.avg_cost)}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(row.total_value)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.min_stock > 0
                                                    ? formatQuantity(row.min_stock)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={stockStatusVariant(row.stock_status)}
                                                >
                                                    {row.stock_status_label ||
                                                        stockStatusLabel(row.stock_status)}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={balances} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
