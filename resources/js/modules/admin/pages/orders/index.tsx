import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import SalesRowActions from '@/components/admin/sales-row-actions';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import {
    formatCurrency,
    formatDate,
    orderSourceLabel,
    orderStatusLabel,
    orderStatusVariant,
} from '@/lib/format';
import type { Paginated } from '@/types/admin';

type InvoiceSummary = {
    id: number;
    invoice_number?: string | null;
    status: string;
    total: number;
};

type OrderRow = {
    id: number;
    order_number?: string | null;
    type: string;
    status: string;
    source?: string | null;
    total: number;
    created_at?: string | null;
    customer_name?: string | null;
    invoice?: InvoiceSummary | null;
};

type Props = {
    orders: Paginated<OrderRow>;
};

export default function OrdersIndex({ orders }: Props) {
    const { t } = useI18n();
    const items = orders.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.orders')} />
            <PageHeader
                title={t('admin.nav.orders')}
                description={t('admin.orders.description')}
            />
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.orders.order_number')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.orders.source')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.orders.customer')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.total')}</th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.orders.invoice')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            {t('admin.orders.empty')}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">
                                                {item.order_number ?? `#${item.id}`}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.source ? orderSourceLabel(item.source) : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.customer_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={orderStatusVariant(item.status)}>
                                                    {orderStatusLabel(item.status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.total)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.invoice ? (
                                                    <span className="font-medium text-emerald-700">
                                                        #{item.invoice.invoice_number}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.created_at
                                                    ? formatDate(item.created_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <SalesRowActions
                                                    viewHref={`/admin/orders/${item.id}`}
                                                    editHref={
                                                        item.invoice &&
                                                        item.invoice.status !== 'void'
                                                            ? `/pos/invoices/${item.invoice.id}/edit`
                                                            : undefined
                                                    }
                                                    printHref={
                                                        item.invoice
                                                            ? `/pos/invoices/${item.invoice.id}/print`
                                                            : undefined
                                                    }
                                                    viewPermission="orders.view"
                                                    editPermission="sales.update"
                                                    printPermission="sales.print_invoice"
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={orders} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
