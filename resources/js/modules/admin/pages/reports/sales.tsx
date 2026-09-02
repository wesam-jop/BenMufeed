import { Head, Link, router } from '@inertiajs/react';
import {
    BarChart3,
    Filter,
    Package,
    Receipt,
    RotateCcw,
    ShoppingCart,
    TrendingUp,
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
    formatDate,
    formatQuantity,
    invoiceStatusLabel,
    invoiceStatusVariant,
    orderSourceLabel,
    paymentMethodLabel,
} from '@/lib/format';
import type { Paginated } from '@/types/admin';

type PaymentRow = { method: string; method_label: string; amount: number };

type InvoiceRow = {
    id: number;
    invoice_number: string;
    status: string;
    status_label: string;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    paid: number;
    balance: number;
    items_count: number;
    issued_at: string | null;
    cashier_name: string | null;
    customer_name: string | null;
    customer_phone: string | null;
    order_number: string | null;
    order_source: string | null;
    payments: PaymentRow[];
    payment_methods_label: string;
};

type Summary = {
    invoices_count: number;
    void_count: number;
    total_sales: number;
    total_paid: number;
    total_tax: number;
    total_discount: number;
    average_ticket: number;
    items_sold: number;
    total_cost: number;
    gross_profit: number;
};

type TopProduct = {
    product_id: number | null;
    product_name: string;
    quantity: number;
    revenue: number;
};

type DailyRow = {
    date: string;
    invoices_count: number;
    total: number;
};

type PaymentBreakdown = {
    method: string;
    method_label: string;
    transactions_count: number;
    total: number;
};

type FilterOption = { value: string; label: string };
type CashierOption = { id: number; name: string };

type Filters = {
    from: string;
    to: string;
    status: string;
    payment_method: string;
    user_id: string;
    search: string;
};

type Props = {
    invoices: Paginated<InvoiceRow>;
    summary: Summary;
    topProducts: TopProduct[];
    dailyBreakdown: DailyRow[];
    paymentBreakdown: PaymentBreakdown[];
    filters: Filters;
    filterOptions: {
        statuses: FilterOption[];
        paymentMethods: FilterOption[];
        cashiers: CashierOption[];
    };
};

export default function SalesReport({
    invoices,
    summary,
    topProducts,
    dailyBreakdown,
    paymentBreakdown,
    filters,
    filterOptions,
}: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);

    const updateFilter = (key: keyof Filters, value: string) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const applyFilters = () => {
        router.get('/admin/reports/sales', localFilters, {
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
            status: '',
            payment_method: '',
            user_id: '',
            search: '',
        };
        setLocalFilters(reset);
        router.get('/admin/reports/sales', reset, { preserveState: true });
    };

    const rows = invoices.data ?? [];

    return (
        <>
            <Head title={t('admin.reports.sales.title')} />
            <PageHeader
                title={t('admin.reports.sales.title')}
                description={t('admin.reports.sales.description')
                    .replace(':from', formatDate(filters.from))
                    .replace(':to', formatDate(filters.to))}
            />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('admin.reports.sales.filters_title')}
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
                            <Label htmlFor="status">{t('admin.reports.sales.invoice_status')}</Label>
                            <Select
                                value={localFilters.status || '__default__'}
                                onValueChange={(v) =>
                                    updateFilter('status', v === '__default__' ? '' : v)
                                }
                            >
                                <SelectTrigger id="status" className="h-11 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.statuses.map((option) => (
                                        <SelectItem
                                            key={option.value || '__default__'}
                                            value={option.value || '__default__'}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="payment_method">
                                {t('admin.reports.sales.payment_method')}
                            </Label>
                            <Select
                                value={localFilters.payment_method || '__all__'}
                                onValueChange={(v) =>
                                    updateFilter('payment_method', v === '__all__' ? '' : v)
                                }
                            >
                                <SelectTrigger id="payment_method" className="h-11 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.paymentMethods.map((option) => (
                                        <SelectItem
                                            key={option.value || '__all__'}
                                            value={option.value || '__all__'}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="user_id">{t('admin.reports.sales.cashier')}</Label>
                            <Select
                                value={localFilters.user_id || '__all__'}
                                onValueChange={(v) =>
                                    updateFilter('user_id', v === '__all__' ? '' : v)
                                }
                            >
                                <SelectTrigger id="user_id" className="h-11 w-full">
                                    <SelectValue placeholder={t('admin.reports.sales.all_cashiers')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">
                                        {t('admin.reports.sales.all_cashiers')}
                                    </SelectItem>
                                    {filterOptions.cashiers.map((cashier) => (
                                        <SelectItem key={cashier.id} value={String(cashier.id)}>
                                            {cashier.name}
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
                                placeholder={t('admin.reports.sales.search_placeholder')}
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
                    title={t('admin.reports.sales.stat_total_sales')}
                    value={summary.total_sales}
                    format="currency"
                    icon={TrendingUp}
                    description={t('admin.reports.sales.stat_invoices_desc').replace(
                        ':count',
                        String(summary.invoices_count),
                    )}
                />
                <StatCard
                    title={t('admin.reports.sales.stat_avg_ticket')}
                    value={summary.average_ticket}
                    format="currency"
                    icon={Receipt}
                />
                <StatCard
                    title={t('admin.reports.sales.stat_gross_profit')}
                    value={summary.gross_profit}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.reports.sales.stat_cost_desc').replace(
                        ':cost',
                        formatCurrency(summary.total_cost),
                    )}
                />
                <StatCard
                    title={t('admin.reports.sales.stat_items_sold')}
                    value={summary.items_sold}
                    format="number"
                    icon={Package}
                    description={t('admin.reports.sales.stat_discount_tax_desc')
                        .replace(':discount', formatCurrency(summary.total_discount))
                        .replace(':tax', formatCurrency(summary.total_tax))}
                />
            </div>

            {summary.void_count > 0 && (
                <div className="mb-6">
                    <Badge variant="destructive">
                        {t('admin.reports.sales.void_count').replace(
                            ':count',
                            String(summary.void_count),
                        )}
                    </Badge>
                </div>
            )}

            <div className="mb-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <BarChart3 className="size-4" />
                            {t('admin.reports.sales.daily_sales')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {dailyBreakdown.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                                {t('admin.reports.sales.empty_daily')}
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50 text-right">
                                            <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.sales.col_invoices')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.sales.col_total')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {dailyBreakdown.map((day) => (
                                            <tr key={day.date} className="border-b">
                                                <td className="px-4 py-3">
                                                    {formatDate(day.date)}
                                                </td>
                                                <td className="px-4 py-3">{day.invoices_count}</td>
                                                <td className="px-4 py-3 font-medium">
                                                    {formatCurrency(day.total)}
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
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Wallet className="size-4" />
                            {t('admin.reports.sales.payment_methods')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {paymentBreakdown.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                                {t('admin.reports.sales.empty_payments')}
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50 text-right">
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.sales.col_method')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.sales.col_transactions')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.sales.col_amount')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.reports.sales.col_percent')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {paymentBreakdown.map((row) => (
                                            <tr key={row.method} className="border-b">
                                                <td className="px-4 py-3">
                                                    {row.method_label ||
                                                        paymentMethodLabel(row.method)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {row.transactions_count}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {formatCurrency(row.total)}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {summary.total_paid > 0
                                                        ? `${((row.total / summary.total_paid) * 100).toFixed(1)}%`
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
            </div>

            <Card className="mb-6">
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <ShoppingCart className="size-4" />
                        {t('admin.reports.sales.top_products')}
                    </CardTitle>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/reports/products">
                            {t('admin.reports.sales.products_report_link')}
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent className="p-0">
                    {topProducts.length === 0 ? (
                        <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                            {t('admin.reports.sales.empty_top_products')}
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">#</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_product')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_qty')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_revenue')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_percent')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topProducts.map((product, index) => (
                                        <tr key={`${product.product_id}-${product.product_name}`} className="border-b">
                                            <td className="px-4 py-3">{index + 1}</td>
                                            <td className="px-4 py-3 font-medium">
                                                {product.product_name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatQuantity(product.quantity)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(product.revenue)}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {summary.total_sales > 0
                                                    ? `${((product.revenue / summary.total_sales) * 100).toFixed(1)}%`
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
                    <CardTitle>{t('admin.reports.sales.invoice_details')}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {rows.length === 0 ? (
                        <div className="flex flex-col items-center gap-4 py-16 text-center">
                            <Receipt className="size-10 text-muted-foreground" />
                            <p className="text-muted-foreground">
                                {t('admin.reports.sales.empty_invoices')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_invoice')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_customer')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_cashier')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_payment')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.reports.sales.col_items')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.total')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row) => (
                                        <tr key={row.id} className="border-b hover:bg-muted/20">
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/admin/invoices/${row.id}`}
                                                    className="font-mono font-medium hover:underline"
                                                >
                                                    {row.invoice_number}
                                                </Link>
                                                {row.order_number && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {row.order_number}
                                                        {row.order_source &&
                                                            ` · ${orderSourceLabel(row.order_source)}`}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.issued_at
                                                    ? formatDate(row.issued_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.customer_name ?? '—'}
                                                {row.customer_phone && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {row.customer_phone}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.cashier_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.payment_methods_label || '—'}
                                            </td>
                                            <td className="px-4 py-3">{row.items_count}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">
                                                    {formatCurrency(row.total)}
                                                </div>
                                                {row.discount > 0 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {t('admin.reports.sales.col_discount').replace(
                                                            ':amount',
                                                            formatCurrency(row.discount),
                                                        )}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={invoiceStatusVariant(row.status)}>
                                                    {row.status_label ||
                                                        invoiceStatusLabel(row.status)}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={invoices} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
