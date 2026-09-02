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
import { formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type SessionSummary = {
    id: number;
    status: string;
    status_label: string;
    session_date: string | null;
    user_name: string | null;
    opened_at: string | null;
};

type CashRegisterRow = {
    id: number;
    name: string;
    is_active: boolean;
    sessions_count: number;
    open_sessions_count: number;
    latest_session: SessionSummary | null;
};

type Summary = {
    registers_count: number;
    active_count: number;
    open_sessions_count: number;
};

type Props = {
    cashRegisters: Paginated<CashRegisterRow>;
    summary: Summary;
};

export default function CashRegistersIndex({ cashRegisters, summary }: Props) {
    const { t } = useI18n();
    const items = cashRegisters.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.cash_registers')} />
            <PageHeader
                title={t('admin.nav.cash_registers')}
                description={t('admin.cash_registers.description')}
                createHref="/admin/cash-registers/create"
                createLabel={t('admin.cash_registers.create')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title={t('admin.cash_registers.count')}
                    value={summary.registers_count}
                    format="number"
                    icon={CreditCard}
                    description={`${summary.active_count} ${t('common.active')}`}
                />
                <StatCard
                    title={t('admin.cash_registers.open_sessions')}
                    value={summary.open_sessions_count}
                    format="number"
                    icon={Wallet}
                    description={t('admin.cash_registers.open_sessions_desc')}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <CreditCard className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.cash_registers.empty_title')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.cash_registers.empty_desc')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/cash-registers/create">
                                {t('admin.cash_registers.create')}
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
                                        <th className="px-4 py-3 font-medium">{t('common.name')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.cash_registers.col_sessions')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.cash_registers.col_latest')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{item.name}</td>
                                            <td className="px-4 py-3">
                                                {t('admin.cash_registers.sessions_count').replace(
                                                    ':count',
                                                    String(item.sessions_count),
                                                )}
                                                {item.open_sessions_count > 0 && (
                                                    <span className="mr-2 text-xs text-emerald-700">
                                                        (
                                                        {t(
                                                            'admin.cash_registers.open_count',
                                                        ).replace(
                                                            ':count',
                                                            String(item.open_sessions_count),
                                                        )}
                                                        )
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.latest_session ? (
                                                    <div>
                                                        <div>
                                                            {item.latest_session.status_label}
                                                            {item.latest_session.user_name
                                                                ? ` · ${item.latest_session.user_name}`
                                                                : ''}
                                                        </div>
                                                        <div className="text-xs">
                                                            {item.latest_session.opened_at
                                                                ? formatDate(
                                                                      item.latest_session.opened_at,
                                                                  )
                                                                : '—'}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        item.is_active ? 'default' : 'secondary'
                                                    }
                                                >
                                                    {item.is_active
                                                        ? t('common.active')
                                                        : t('common.inactive')}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link
                                                            href={`/admin/cash-registers/${item.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={`/admin/cash-registers/${item.id}/edit`}
                                                        deleteHref={`/admin/cash-registers/${item.id}`}
                                                        editPermission="cash-registers.update"
                                                        deletePermission="cash-registers.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={cashRegisters} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
