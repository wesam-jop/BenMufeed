import { Head, Link } from '@inertiajs/react';
import { BookOpen, Eye, Landmark, Layers } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { accountTypeLabel, formatCurrency } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type AccountRow = {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    parent_code: string | null;
    parent_name: string | null;
    is_system: boolean;
    is_active: boolean;
    children_count: number;
    journal_lines_count: number;
    expenses_count: number;
    debit: number;
    credit: number;
    balance: number;
};

type Summary = {
    accounts_count: number;
    active_count: number;
    system_count: number;
    by_type: Record<string, number>;
    total_debit: number;
    total_credit: number;
};

type Props = {
    accounts: Paginated<AccountRow>;
    summary: Summary;
};

export default function ChartOfAccountsIndex({ accounts, summary }: Props) {
    const { t } = useI18n();
    const items = accounts.data ?? [];

    return (
        <>
            <Head title={t('admin.chart_of_accounts.title')} />
            <PageHeader
                title={t('admin.chart_of_accounts.title')}
                description={t('admin.chart_of_accounts.description')}
                createHref="/admin/chart-of-accounts/create"
                createLabel={t('admin.chart_of_accounts.new')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.chart_of_accounts.stat_count')}
                    value={summary.accounts_count}
                    format="number"
                    icon={BookOpen}
                    description={t('admin.chart_of_accounts.stat_count_desc')
                        .replace(':active', String(summary.active_count))
                        .replace(':system', String(summary.system_count))}
                />
                <StatCard
                    title={t('admin.chart_of_accounts.stat_debit')}
                    value={summary.total_debit}
                    format="currency"
                    icon={Landmark}
                    description={t('admin.chart_of_accounts.stat_debit_desc')}
                />
                <StatCard
                    title={t('admin.chart_of_accounts.stat_credit')}
                    value={summary.total_credit}
                    format="currency"
                    icon={Layers}
                    description={t('admin.chart_of_accounts.stat_credit_desc')}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <BookOpen className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.chart_of_accounts.empty')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.chart_of_accounts.empty_hint')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/chart-of-accounts/create">
                                {t('admin.chart_of_accounts.new')}
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
                                            {t('admin.chart_of_accounts.col_code')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.name')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.chart_of_accounts.col_type')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.chart_of_accounts.col_parent')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.chart_of_accounts.col_balance')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-mono">{item.code}</td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/admin/chart-of-accounts/${item.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {item.name}
                                                </Link>
                                                {item.is_system && (
                                                    <Badge variant="outline" className="ms-2">
                                                        {t('admin.chart_of_accounts.system')}
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {item.type_label || accountTypeLabel(item.type)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.parent_name
                                                    ? `${item.parent_code} — ${item.parent_name}`
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(item.balance)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={item.is_active ? 'default' : 'secondary'}
                                                >
                                                    {item.is_active
                                                        ? t('common.active')
                                                        : t('common.suspended')}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link
                                                            href={`/admin/chart-of-accounts/${item.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={`/admin/chart-of-accounts/${item.id}/edit`}
                                                        deleteHref={
                                                            item.is_system
                                                                ? undefined
                                                                : `/admin/chart-of-accounts/${item.id}`
                                                        }
                                                        editPermission="chart-of-accounts.update"
                                                        deletePermission="chart-of-accounts.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={accounts} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
