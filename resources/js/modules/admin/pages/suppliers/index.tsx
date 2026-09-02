import { Head, Link } from '@inertiajs/react';
import { Eye, Truck, Users, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type SupplierRow = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    balance: number;
    is_active: boolean;
    purchase_invoices_count: number;
};

type Summary = {
    suppliers_count: number;
    active_count: number;
    total_balance: number;
};

type Props = {
    suppliers: Paginated<SupplierRow>;
    summary: Summary;
};

export default function SuppliersIndex({ suppliers, summary }: Props) {
    const { t } = useI18n();
    const items = suppliers.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.suppliers')} />
            <PageHeader
                title={t('admin.nav.suppliers')}
                description={t('admin.suppliers.description')}
                createHref="/admin/suppliers/create"
                createLabel={t('admin.suppliers.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title={t('admin.suppliers.count')}
                    value={summary.suppliers_count}
                    format="number"
                    icon={Users}
                    description={`${summary.active_count} ${t('common.active')}`}
                />
                <StatCard
                    title={t('admin.suppliers.total_balance')}
                    value={summary.total_balance}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.suppliers.total_balance_desc')}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <Truck className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.suppliers.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.suppliers.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/suppliers/create">{t('admin.suppliers.create')}</Link>
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
                                            {t('admin.suppliers.col_contact')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.phone')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.suppliers.col_invoices')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.suppliers.col_balance')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{item.name}</td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.contact_person ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.phone ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {t('admin.suppliers.invoices_count').replace(
                                                    ':count',
                                                    String(item.purchase_invoices_count),
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.balance)}
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
                                                        <Link href={`/admin/suppliers/${item.id}`}>
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={`/admin/suppliers/${item.id}/edit`}
                                                        deleteHref={`/admin/suppliers/${item.id}`}
                                                        editPermission="suppliers.update"
                                                        deletePermission="suppliers.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={suppliers} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
