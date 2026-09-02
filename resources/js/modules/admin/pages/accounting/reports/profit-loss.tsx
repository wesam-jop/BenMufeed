import { Head, Link, router } from '@inertiajs/react';
import { Filter, TrendingDown, TrendingUp, Wallet } from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency, formatDate } from '@/lib/format';

type AccountLine = {
    account_id: number;
    code: string;
    name: string;
    amount: number;
};

type Summary = {
    total_revenue: number;
    total_cogs: number;
    gross_profit: number;
    total_expenses: number;
    net_profit: number;
};

type Report = {
    revenue: AccountLine[];
    cogs: AccountLine[];
    expenses: AccountLine[];
    summary: Summary;
};

type Props = {
    report: Report;
    filters: {
        from: string;
        to: string;
    };
};

export default function ProfitLossReport({ report, filters }: Props) {
    const { t } = useI18n();
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const applyFilters = () => {
        router.get(
            '/admin/accounting/reports/profit-loss',
            { from, to },
            { preserveState: true, preserveScroll: true },
        );
    };

    const { summary } = report;
    const isProfit = summary.net_profit >= 0;

    return (
        <>
            <Head title={t('admin.reports.profit_loss.title')} />
            <PageHeader
                title={t('admin.reports.profit_loss.title')}
                description={t('admin.reports.profit_loss.description')
                    .replace(':from', formatDate(filters.from))
                    .replace(':to', formatDate(filters.to))}
            />

            <Card className="mb-6 max-w-2xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('admin.reports.profit_loss.period')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="from">{t('common.from_date')}</Label>
                            <Input
                                id="from"
                                type="date"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="to">{t('common.to_date')}</Label>
                            <Input
                                id="to"
                                type="date"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                            />
                        </div>
                        <Button onClick={applyFilters}>{t('common.apply')}</Button>
                    </div>
                </CardContent>
            </Card>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.reports.profit_loss.stat_revenue')}
                    value={summary.total_revenue}
                    format="currency"
                    icon={TrendingUp}
                />
                <StatCard
                    title={t('admin.reports.profit_loss.stat_cogs')}
                    value={summary.total_cogs}
                    format="currency"
                    icon={TrendingDown}
                />
                <StatCard
                    title={t('admin.reports.profit_loss.stat_gross_profit')}
                    value={summary.gross_profit}
                    format="currency"
                    icon={Wallet}
                />
                <StatCard
                    title={
                        isProfit
                            ? t('admin.reports.profit_loss.stat_net_profit')
                            : t('admin.reports.profit_loss.stat_net_loss')
                    }
                    value={Math.abs(summary.net_profit)}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.reports.profit_loss.expenses_desc').replace(
                        ':amount',
                        formatCurrency(summary.total_expenses),
                    )}
                />
            </div>

            <div className="mb-6">
                <Badge variant={isProfit ? 'default' : 'destructive'} className="text-sm px-3 py-1">
                    {isProfit
                        ? t('admin.reports.profit_loss.badge_profit')
                        : t('admin.reports.profit_loss.badge_loss')}
                    : {formatCurrency(Math.abs(summary.net_profit))}
                </Badge>
            </div>

            <div className="grid gap-6">
                <ReportSection
                    title={t('admin.reports.profit_loss.section_revenue')}
                    lines={report.revenue}
                    total={summary.total_revenue}
                    emptyMessage={t('admin.reports.profit_loss.empty_revenue')}
                />

                <ReportSection
                    title={t('admin.reports.profit_loss.section_cogs')}
                    lines={report.cogs}
                    total={summary.total_cogs}
                    emptyMessage={t('admin.reports.profit_loss.empty_cogs')}
                />

                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="flex items-center justify-between py-4">
                        <span className="font-semibold">
                            {t('admin.reports.profit_loss.gross_profit')}
                        </span>
                        <span className="text-lg font-bold">{formatCurrency(summary.gross_profit)}</span>
                    </CardContent>
                </Card>

                <ReportSection
                    title={t('admin.reports.profit_loss.section_expenses')}
                    lines={report.expenses}
                    total={summary.total_expenses}
                    emptyMessage={t('admin.reports.profit_loss.empty_expenses')}
                />

                <Card className={isProfit ? 'border-green-500/30 bg-green-500/5' : 'border-destructive/30 bg-destructive/5'}>
                    <CardContent className="flex items-center justify-between py-4">
                        <span className="font-semibold">
                            {isProfit
                                ? t('admin.reports.profit_loss.net_profit')
                                : t('admin.reports.profit_loss.net_loss')}
                        </span>
                        <span className="text-lg font-bold">{formatCurrency(Math.abs(summary.net_profit))}</span>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function ReportSection({
    title,
    lines,
    total,
    emptyMessage,
}: {
    title: string;
    lines: AccountLine[];
    total: number;
    emptyMessage: string;
}) {
    const { t } = useI18n();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
                {lines.length === 0 ? (
                    <p className="px-4 py-8 text-center text-sm text-muted-foreground">{emptyMessage}</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.reports.profit_loss.col_code')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.reports.profit_loss.col_account')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.reports.profit_loss.col_amount')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {lines.map((line) => (
                                    <tr key={line.account_id} className="border-b hover:bg-muted/30">
                                        <td className="px-4 py-3 font-mono">
                                            <Link
                                                href={`/admin/chart-of-accounts/${line.account_id}`}
                                                className="hover:underline"
                                            >
                                                {line.code}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">{line.name}</td>
                                        <td className="px-4 py-3 font-medium">
                                            {formatCurrency(line.amount)}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="border-t bg-muted/30 font-semibold">
                                    <td className="px-4 py-3" colSpan={2}>
                                        {t('admin.reports.profit_loss.section_total').replace(
                                            ':title',
                                            title,
                                        )}
                                    </td>
                                    <td className="px-4 py-3">{formatCurrency(total)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
