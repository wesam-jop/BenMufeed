import { Head, Link } from '@inertiajs/react';
import { Eye, Receipt, Wallet } from 'lucide-react';
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

type ExpenseRow = {
    id: number;
    category: string;
    description: string | null;
    amount: number;
    payment_method: string;
    payment_method_label: string;
    expense_date: string | null;
    user_name: string | null;
};

type Summary = {
    expenses_count: number;
    total_amount: number;
    this_month_amount: number;
    this_month_count: number;
};

type Props = {
    expenses: Paginated<ExpenseRow>;
    summary: Summary;
};

export default function ExpensesIndex({ expenses, summary }: Props) {
    const { t } = useI18n();
    const items = expenses.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.expenses')} />
            <PageHeader
                title={t('admin.nav.expenses')}
                description={t('admin.expenses.description')}
                createHref="/admin/expenses/create"
                createLabel={t('admin.expenses.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title={t('admin.expenses.count')}
                    value={summary.expenses_count}
                    format="number"
                    icon={Receipt}
                />
                <StatCard
                    title={t('admin.expenses.total_amount')}
                    value={summary.total_amount}
                    format="currency"
                    icon={Wallet}
                />
                <StatCard
                    title={t('admin.expenses.this_month')}
                    value={summary.this_month_amount}
                    format="currency"
                    description={t('admin.expenses.expenses_count').replace(
                        ':count',
                        String(summary.this_month_count),
                    )}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <Receipt className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.expenses.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.expenses.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/expenses/create">{t('admin.expenses.create')}</Link>
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
                                            {t('admin.expenses.col_category')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.expenses.col_description')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.amount')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.expenses.col_payment')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.expenses.col_by')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">{item.category}</Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.description ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(item.amount)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.payment_method_label}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.expense_date ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.user_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/expenses/${item.id}`}>
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={`/admin/expenses/${item.id}/edit`}
                                                        deleteHref={`/admin/expenses/${item.id}`}
                                                        editPermission="expenses.update"
                                                        deletePermission="expenses.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={expenses} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
