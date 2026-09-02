import { Head, Link } from '@inertiajs/react';
import { CreditCard, Eye, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency, formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type SessionRow = {
    id: number;
    cash_register_name: string;
    user_name: string | null;
    status: string;
    status_label: string;
    session_date: string | null;
    opening_balance: number;
    expected_cash: number;
    actual_cash: number | null;
    difference: number | null;
    opened_at: string | null;
    can_close: boolean;
};

type Summary = {
    sessions_count: number;
    open_count: number;
    closed_count: number;
    total_expected: number;
};

type Props = {
    cashRegisterSessions: Paginated<SessionRow>;
    summary: Summary;
};

export default function CashRegisterSessionsIndex({
    cashRegisterSessions,
    summary,
}: Props) {
    const { t } = useI18n();
    const items = cashRegisterSessions.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.cash_sessions')} />
            <PageHeader
                title={t('admin.nav.cash_sessions')}
                description={t('admin.cash_sessions.description')}
                createHref="/admin/cash-register-sessions/create"
                createLabel={t('admin.cash_sessions.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.cash_sessions.total')}
                    value={summary.sessions_count}
                    format="number"
                    icon={CreditCard}
                />
                <StatCard
                    title={t('admin.cash_sessions.open')}
                    value={summary.open_count}
                    format="number"
                    description={t('admin.cash_sessions.open_desc')}
                />
                <StatCard
                    title={t('admin.cash_sessions.closed')}
                    value={summary.closed_count}
                    format="number"
                />
                <StatCard
                    title={t('admin.cash_sessions.expected_cash')}
                    value={summary.total_expected}
                    format="currency"
                    icon={Wallet}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <CreditCard className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.cash_sessions.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.cash_sessions.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/cash-register-sessions/create">
                                {t('admin.cash_sessions.create')}
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
                                            {t('admin.cash_sessions.col_register')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.cash_sessions.col_cashier')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.cash_sessions.col_opening')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.cash_sessions.col_expected')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">
                                                {item.cash_register_name}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.user_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.opened_at
                                                    ? formatDate(item.opened_at)
                                                    : item.session_date ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.opening_balance)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.expected_cash)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        item.status === 'open'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {item.status_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link
                                                            href={`/admin/cash-register-sessions/${item.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    {item.can_close && (
                                                        <ResourceActions
                                                            deleteHref={`/admin/cash-register-sessions/${item.id}`}
                                                            deletePermission="cash-register-sessions.delete"
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
                            <PaginationLinks paginator={cashRegisterSessions} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
