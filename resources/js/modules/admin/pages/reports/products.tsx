import { Head, Link, router } from '@inertiajs/react';
import { Filter, Layers, Package, RotateCcw, ShoppingCart, TrendingUp, Wallet } from 'lucide-react';
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
import { formatCurrency, formatDate, formatQuantity } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type ProductRow = {
    product_id: number | null;
    product_name: string;
    sku: string | null;
    category_name: string | null;
    product_type: string | null;
    quantity: number;
    revenue: number;
    cost: number;
    profit: number;
    margin_percent: number;
    invoices_count: number;
    avg_unit_price: number;
    list_price: number | null;
};

type Summary = {
    products_count: number;
    total_quantity: number;
    total_revenue: number;
    total_cost: number;
    gross_profit: number;
    average_margin: number;
    average_unit_price: number;
};

type CategoryRow = {
    category_name: string;
    products_count: number;
    quantity: number;
    revenue: number;
};

type FilterOption = { value: string; label: string };

type Filters = {
    from: string;
    to: string;
    search: string;
    sort: string;
};

type Props = {
    items: Paginated<ProductRow>;
    summary: Summary;
    categoryBreakdown: CategoryRow[];
    filters: Filters;
    filterOptions: { sorts: FilterOption[] };
};

export default function ProductsReport({
    items,
    summary,
    categoryBreakdown,
    filters,
    filterOptions,
}: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);
    const rows = items.data ?? [];

    const updateFilter = (key: keyof Filters, value: string) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const applyFilters = () => {
        router.get('/admin/reports/products', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        const reset: Filters = {
            from: new Date(new Date().getFullYear(), new Date().getMonth(), 1)
                .toISOString()
                .slice(0, 10),
            to: new Date().toISOString().slice(0, 10),
            search: '',
            sort: 'revenue',
        };
        setLocalFilters(reset);
        router.get('/admin/reports/products', reset, { preserveState: true });
    };

    return (
        <>
            <Head title={t('admin.reports.products.title')} />
            <PageHeader
                title={t('admin.reports.products.title')}
                description={t('admin.reports.products.description')
                    .replace(':from', formatDate(filters.from))
                    .replace(':to', formatDate(filters.to))}
            />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('admin.reports.products.filters_title')}
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
                            <Label htmlFor="sort">{t('admin.reports.products.sort_by')}</Label>
                            <Select
                                value={localFilters.sort}
                                onValueChange={(value) => updateFilter('sort', value)}
                            >
                                <SelectTrigger id="sort" className="h-11 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.sorts.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2 sm:col-span-2 xl:col-span-1">
                            <Label htmlFor="search">{t('common.search')}</Label>
                            <Input
                                id="search"
                                type="search"
                                value={localFilters.search}
                                onChange={(e) => updateFilter('search', e.target.value)}
                                placeholder={t('admin.reports.products.search_placeholder')}
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
                    title={t('admin.reports.products.stat_products')}
                    value={summary.products_count}
                    format="number"
                    icon={Package}
                    description={t('admin.reports.products.stat_units_sold').replace(
                        ':qty',
                        formatQuantity(summary.total_quantity),
                    )}
                />
                <StatCard
                    title={t('admin.reports.products.stat_revenue')}
                    value={summary.total_revenue}
                    format="currency"
                    icon={TrendingUp}
                />
                <StatCard
                    title={t('admin.reports.products.stat_gross_profit')}
                    value={summary.gross_profit}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.reports.products.stat_margin_desc').replace(
                        ':margin',
                        String(summary.average_margin),
                    )}
                />
                <StatCard
                    title={t('admin.reports.products.stat_avg_unit')}
                    value={summary.average_unit_price}
                    format="currency"
                    icon={ShoppingCart}
                    description={t('admin.reports.products.stat_cost_desc').replace(
                        ':cost',
                        formatCurrency(summary.total_cost),
                    )}
                />
            </div>

            <div className="mb-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Layers className="size-4" />
                            {t('admin.reports.products.by_category')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {categoryBreakdown.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                                {t('admin.reports.products.empty_categories')}
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50 text-right">
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.products.col_category')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.products.col_products')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.products.col_qty')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.products.col_revenue')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.products.col_percent')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {categoryBreakdown.map((row) => (
                                            <tr key={row.category_name} className="border-b">
                                                <td className="px-4 py-3 font-medium">
                                                    {row.category_name}
                                                </td>
                                                <td className="px-4 py-3">{row.products_count}</td>
                                                <td className="px-4 py-3">
                                                    {formatQuantity(row.quantity)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {formatCurrency(row.revenue)}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {summary.total_revenue > 0
                                                        ? `${((row.revenue / summary.total_revenue) * 100).toFixed(1)}%`
                                                        : '—'}
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
                    <CardHeader>
                        <CardTitle>{t('admin.reports.products.profitability_summary')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <SummaryRow
                            label={t('admin.reports.products.label_revenue')}
                            value={formatCurrency(summary.total_revenue)}
                        />
                        <SummaryRow
                            label={t('admin.reports.products.label_cost')}
                            value={formatCurrency(summary.total_cost)}
                        />
                        <SummaryRow
                            label={t('admin.reports.products.label_gross_profit')}
                            value={formatCurrency(summary.gross_profit)}
                            bold
                        />
                        <SummaryRow
                            label={t('admin.reports.products.label_avg_margin')}
                            value={`${summary.average_margin}%`}
                        />
                        <div className="pt-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/admin/reports/sales">
                                    {t('admin.reports.products.sales_report_link')}
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{t('admin.reports.products.product_details')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {rows.length === 0 ? (
                        <div className="flex flex-col items-center gap-4 py-16 text-center">
                            <Package className="size-10 text-muted-foreground" />
                            <p className="text-muted-foreground">{t('admin.reports.products.empty')}</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">#</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_product')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_category')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_qty')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_invoices')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_revenue')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_cost')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_profit')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.products.col_margin')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row, index) => (
                                        <tr key={`${row.product_id}-${row.product_name}`} className="border-b hover:bg-muted/20">
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {(items.from ?? 0) + index}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.product_id ? (
                                                    <Link
                                                        href={`/admin/products/${row.product_id}`}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {row.product_name}
                                                    </Link>
                                                ) : (
                                                    row.product_name
                                                )}
                                                {row.sku && (
                                                    <p className="text-xs text-muted-foreground font-mono">
                                                        {row.sku}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.category_name ? (
                                                    <Badge variant="secondary">{row.category_name}</Badge>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3">{formatQuantity(row.quantity)}</td>
                                            <td className="px-4 py-3">{row.invoices_count}</td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(row.revenue)}
                                                {row.avg_unit_price > 0 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatCurrency(row.avg_unit_price)}{' '}
                                                        {t('admin.reports.products.per_unit')}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">{formatCurrency(row.cost)}</td>
                                            <td
                                                className={`px-4 py-3 font-medium ${row.profit < 0 ? 'text-destructive' : ''}`}
                                            >
                                                {formatCurrency(row.profit)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        row.margin_percent >= 30
                                                            ? 'default'
                                                            : row.margin_percent >= 10
                                                              ? 'secondary'
                                                              : 'outline'
                                                    }
                                                >
                                                    {row.margin_percent}%
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={items} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}

function SummaryRow({
    label,
    value,
    bold = false,
}: {
    label: string;
    value: string;
    bold?: boolean;
}) {
    return (
        <div className={`flex items-center justify-between ${bold ? 'font-semibold text-lg' : ''}`}>
            <span className="text-muted-foreground">{label}</span>
            <span>{value}</span>
        </div>
    );
}
