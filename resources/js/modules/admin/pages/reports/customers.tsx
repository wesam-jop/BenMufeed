import { Head, Link, router } from '@inertiajs/react';
import {
    Crown,
    Filter,
    Receipt,
    RotateCcw,
    ShoppingCart,
    TrendingUp,
    Users,
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
import { formatCurrency, formatDate, formatNumber } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type CustomerRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    whatsapp: string | null;
    is_active: boolean;
    loyalty_points: number;
    invoices_count: number;
    orders_count: number;
    total_spent: number;
    average_spent: number;
    last_purchase_at: string | null;
    registered_at: string | null;
};

type TopCustomerRow = CustomerRow;

type Summary = {
    customers_count: number;
    active_count: number;
    total_invoices: number;
    total_orders: number;
    total_revenue: number;
    average_revenue: number;
    total_loyalty_points: number;
};

type FilterOption = { value: string; label: string };

type Filters = {
    from: string;
    to: string;
    search: string;
    status: string;
    activity: string;
    sort: string;
};

type Props = {
    customers: Paginated<CustomerRow>;
    summary: Summary;
    topCustomers: TopCustomerRow[];
    filters: Filters;
    filterOptions: {
        statuses: FilterOption[];
        activities: FilterOption[];
        sorts: FilterOption[];
    };
};

export default function CustomersReport({
    customers,
    summary,
    topCustomers,
    filters,
    filterOptions,
}: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);
    const rows = customers.data ?? [];

    const updateFilter = (key: keyof Filters, value: string) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const applyFilters = () => {
        router.get('/admin/reports/customers', localFilters, {
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
            status: 'all',
            activity: 'with_sales',
            sort: 'revenue',
        };
        setLocalFilters(reset);
        router.get('/admin/reports/customers', reset, { preserveState: true });
    };

    const salesReportHref = (row: CustomerRow) =>
        `/admin/reports/sales?from=${encodeURIComponent(localFilters.from)}&to=${encodeURIComponent(localFilters.to)}&search=${encodeURIComponent(row.phone || row.name)}`;

    return (
        <>
            <Head title={t('admin.reports.customers.title')} />
            <PageHeader
                title={t('admin.reports.customers.title')}
                description={t('admin.reports.customers.description')}
            />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('admin.reports.customers.filters_title')}
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
                            <Label htmlFor="activity">
                                {t('admin.reports.customers.activity')}
                            </Label>
                            <Select
                                value={localFilters.activity}
                                onValueChange={(value) => updateFilter('activity', value)}
                            >
                                <SelectTrigger id="activity" className="h-11 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.activities.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="status">
                                {t('admin.reports.customers.account_status')}
                            </Label>
                            <Select
                                value={localFilters.status}
                                onValueChange={(value) => updateFilter('status', value)}
                            >
                                <SelectTrigger id="status" className="h-11 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.statuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="sort">{t('admin.reports.customers.sort_by')}</Label>
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
                        <div className="grid gap-2 sm:col-span-2 xl:col-span-3">
                            <Label htmlFor="search">{t('common.search')}</Label>
                            <Input
                                id="search"
                                type="search"
                                value={localFilters.search}
                                onChange={(e) => updateFilter('search', e.target.value)}
                                placeholder={t('admin.reports.customers.search_placeholder')}
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
                    title={t('admin.reports.customers.stat_customers')}
                    value={summary.customers_count}
                    format="number"
                    icon={Users}
                    description={t('admin.reports.customers.stat_active_desc').replace(
                        ':count',
                        String(summary.active_count),
                    )}
                />
                <StatCard
                    title={t('admin.reports.customers.stat_total_sales')}
                    value={summary.total_revenue}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.reports.customers.stat_invoices_desc').replace(
                        ':count',
                        formatNumber(summary.total_invoices),
                    )}
                />
                <StatCard
                    title={t('admin.reports.customers.stat_avg_purchase')}
                    value={summary.average_revenue}
                    format="currency"
                    icon={TrendingUp}
                    description={t('admin.reports.customers.stat_orders_desc').replace(
                        ':count',
                        formatNumber(summary.total_orders),
                    )}
                />
                <StatCard
                    title={t('admin.reports.customers.stat_loyalty')}
                    value={summary.total_loyalty_points}
                    format="number"
                    icon={Crown}
                />
            </div>

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Crown className="size-4" />
                        {t('admin.reports.customers.top_customers')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {topCustomers.length === 0 ? (
                        <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                            {t('admin.reports.customers.empty_top')}
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">#</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_customer')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_invoices')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_orders')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_total_spent')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_percent')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topCustomers.map((row, index) => (
                                        <tr key={row.id} className="border-b">
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {index + 1}
                                            </td>
                                            <td className="px-4 py-3">
                                                <p className="font-medium">{row.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {row.phone}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">{row.invoices_count}</td>
                                            <td className="px-4 py-3">{row.orders_count}</td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(row.total_spent)}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {summary.total_revenue > 0
                                                    ? `${((row.total_spent / summary.total_revenue) * 100).toFixed(1)}%`
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
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <CardTitle>{t('admin.reports.customers.customer_details')}</CardTitle>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/reports/sales">
                            {t('admin.reports.customers.sales_report_link')}
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent className="p-0">
                    {rows.length === 0 ? (
                        <div className="flex flex-col items-center gap-4 py-16 text-center">
                            <Users className="size-10 text-muted-foreground" />
                            <p className="text-muted-foreground">
                                {t('admin.reports.customers.empty')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_customer')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_contact')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_invoices')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_orders')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_total_spent')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_avg_ticket')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_loyalty')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_last_purchase')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.customers.col_status')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row) => (
                                        <tr key={row.id} className="border-b hover:bg-muted/20">
                                            <td className="px-4 py-3">
                                                <p className="font-medium">{row.name}</p>
                                                {row.registered_at && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {t(
                                                            'admin.reports.customers.registered',
                                                        ).replace(
                                                            ':date',
                                                            formatDate(row.registered_at),
                                                        )}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <p>{row.phone}</p>
                                                {row.email && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {row.email}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={salesReportHref(row)}
                                                    className="inline-flex items-center gap-1 hover:underline"
                                                >
                                                    <Receipt className="size-3.5" />
                                                    {row.invoices_count}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center gap-1">
                                                    <ShoppingCart className="size-3.5 text-muted-foreground" />
                                                    {row.orders_count}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(row.total_spent)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.invoices_count > 0
                                                    ? formatCurrency(row.average_spent)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.loyalty_points > 0 ? (
                                                    <Badge variant="secondary">
                                                        {formatNumber(row.loyalty_points)}
                                                    </Badge>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.last_purchase_at
                                                    ? formatDate(row.last_purchase_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        row.is_active ? 'default' : 'secondary'
                                                    }
                                                >
                                                    {row.is_active
                                                        ? t('common.active')
                                                        : t('common.inactive')}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={customers} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
