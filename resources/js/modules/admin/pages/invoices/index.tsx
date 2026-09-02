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
    invoiceStatusVariant,
    statusLabel,
} from '@/lib/format';
import type { Paginated } from '@/types/admin';

type InvoiceRow = {
    id: number;
    invoice_number?: string | null;
    status: string;
    total: number;
    paid: number;
    created_at?: string | null;
    cashier_name?: string | null;
    order_number?: string | null;
};

type Props = {
    invoices: Paginated<InvoiceRow>;
};

export default function InvoicesIndex({ invoices }: Props) {
    const { t } = useI18n();
    const items = invoices.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.invoices')} />
            <PageHeader
                title={t('admin.nav.invoices')}
                description={t('admin.invoices.description')}
            />
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.invoices.invoice_number')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.invoices.order')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.invoices.cashier')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.total')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            {t('admin.invoices.empty')}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">
                                                #{item.invoice_number ?? item.id}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.order_number ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.cashier_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.total)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={invoiceStatusVariant(item.status)}>
                                                    {statusLabel(item.status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.created_at
                                                    ? formatDate(item.created_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <SalesRowActions
                                                    viewHref={`/admin/invoices/${item.id}`}
                                                    editHref={
                                                        item.status !== 'void'
                                                            ? `/pos/invoices/${item.id}/edit`
                                                            : undefined
                                                    }
                                                    printHref={`/pos/invoices/${item.id}/print`}
                                                    deleteHref={
                                                        item.status !== 'void'
                                                            ? `/pos/invoices/${item.id}`
                                                            : undefined
                                                    }
                                                    viewPermission="invoices.view"
                                                    editPermission="sales.update"
                                                    printPermission="sales.print_invoice"
                                                    deletePermission="sales.cancel"
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={invoices} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
