import { Head, Link } from '@inertiajs/react';
import { Eye, FileWarning, Wallet } from 'lucide-react';
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
    formatQuantity,
    wastageReasonLabel,
} from '@/lib/format';
import type { Paginated } from '@/types/admin';

type WastageRow = {
    id: number;
    reference: string;
    warehouse_name: string;
    product_name: string;
    product_sku: string | null;
    unit: { name: string; abbreviation: string } | null;
    quantity: number;
    reason: string;
    reason_label: string;
    line_value: number;
    user_name: string | null;
    created_at: string | null;
};

type Summary = {
    records_count: number;
    total_value: number;
    this_month_value: number;
    this_month_count: number;
};

type Props = {
    wastageRecords: Paginated<WastageRow>;
    summary: Summary;
};

export default function WastageRecordsIndex({ wastageRecords, summary }: Props) {
    const { t } = useI18n();
    const items = wastageRecords.data ?? [];

    return (
        <>
            <Head title={t('admin.wastage.title')} />
            <PageHeader
                title={t('admin.wastage.title')}
                description={t('admin.wastage.description')}
                createHref="/admin/wastage-records/create"
                createLabel={t('admin.wastage.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.wastage.total_records')}
                    value={summary.records_count}
                    format="number"
                    icon={FileWarning}
                    description={t('admin.wastage.total_records_desc')}
                />
                <StatCard
                    title={t('admin.wastage.total_value')}
                    value={summary.total_value}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.wastage.total_value_desc')}
                />
                <StatCard
                    title={t('admin.wastage.this_month')}
                    value={summary.this_month_count}
                    format="number"
                    icon={FileWarning}
                    description={formatCurrency(summary.this_month_value)}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <FileWarning className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.wastage.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.wastage.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/wastage-records/create">
                                {t('admin.wastage.create')}
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
                                            {t('admin.wastage.col_reference')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.wastage.col_warehouse')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.wastage.col_material')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.wastage.col_quantity')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.wastage.col_reason')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.wastage.col_value')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.wastage.col_by')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{item.reference}</td>
                                            <td className="px-4 py-3">{item.warehouse_name}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{item.product_name}</div>
                                                {item.product_sku && (
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.product_sku}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatQuantity(item.quantity)}
                                                {item.unit?.abbreviation
                                                    ? ` ${item.unit.abbreviation}`
                                                    : ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="destructive">
                                                    {item.reason_label ||
                                                        wastageReasonLabel(item.reason)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.line_value)}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.user_name ?? '—'}
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
                                                            href={`/admin/wastage-records/${item.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        deleteHref={`/admin/wastage-records/${item.id}`}
                                                        deletePermission="wastage.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={wastageRecords} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
