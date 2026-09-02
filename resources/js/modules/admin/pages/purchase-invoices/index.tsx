import { Head, Link } from '@inertiajs/react';
import { Eye, Pencil, Receipt, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import {
    formatCurrency,
    formatDate,
    purchaseInvoiceStatusLabel,
    purchaseInvoiceStatusVariant,
} from '@/lib/format';
import type { Paginated } from '@/types/admin';

type InvoiceRow = {
    id: number;
    invoice_number: string;
    supplier_name: string;
    warehouse_name: string;
    status: string;
    status_label: string;
    items_count: number;
    total: number;
    created_at: string | null;
    can_edit: boolean;
};

type Summary = {
    invoices_count: number;
    draft_count: number;
    posted_count: number;
    total_value: number;
};

type Props = {
    purchaseInvoices: Paginated<InvoiceRow>;
    summary: Summary;
};

export default function PurchaseInvoicesIndex({ purchaseInvoices, summary }: Props) {
    const { t } = useI18n();
    const items = purchaseInvoices.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.purchase_invoices')} />
            <PageHeader
                title={t('admin.nav.purchase_invoices')}
                description={t('admin.purchase_invoices.description')}
                createHref="/admin/purchase-invoices/create"
                createLabel={t('admin.purchase_invoices.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.purchase_invoices.total')}
                    value={summary.invoices_count}
                    format="number"
                    icon={Receipt}
                />
                <StatCard
                    title={t('admin.purchase_invoices.drafts')}
                    value={summary.draft_count}
                    format="number"
                    description={t('admin.purchase_invoices.drafts_desc')}
                />
                <StatCard
                    title={t('admin.purchase_invoices.received')}
                    value={summary.posted_count}
                    format="number"
                    description={t('admin.purchase_invoices.received_desc')}
                />
                <StatCard
                    title={t('admin.purchase_invoices.purchase_value')}
                    value={summary.total_value}
                    format="currency"
                    icon={Wallet}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <Receipt className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.purchase_invoices.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.purchase_invoices.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/purchase-invoices/create">
                                {t('admin.purchase_invoices.create')}
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
                                            {t('admin.purchase_invoices.col_number')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.purchase_invoices.col_supplier')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.purchase_invoices.col_warehouse')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.purchase_invoices.col_materials')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.total')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">
                                                {item.invoice_number}
                                            </td>
                                            <td className="px-4 py-3">{item.supplier_name}</td>
                                            <td className="px-4 py-3">{item.warehouse_name}</td>
                                            <td className="px-4 py-3">
                                                {t('admin.purchase_invoices.materials_count').replace(
                                                    ':count',
                                                    String(item.items_count),
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.total)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={purchaseInvoiceStatusVariant(
                                                        item.status,
                                                    )}
                                                >
                                                    {item.status_label ||
                                                        purchaseInvoiceStatusLabel(item.status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.created_at
                                                    ? formatDate(item.created_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link
                                                            href={`/admin/purchase-invoices/${item.id}`}
                                                        >
                                                            {item.can_edit ? (
                                                                <>
                                                                    <Pencil className="size-4" />
                                                                    {t(
                                                                        'admin.purchase_invoices.continue',
                                                                    )}
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
                                                            deleteHref={`/admin/purchase-invoices/${item.id}`}
                                                            deletePermission="purchase-invoices.delete"
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
                            <PaginationLinks paginator={purchaseInvoices} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
