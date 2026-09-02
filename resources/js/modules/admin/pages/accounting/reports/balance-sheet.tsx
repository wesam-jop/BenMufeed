import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeftRight,
    Filter,
    Landmark,
    Scale,
    TrendingUp,
} from 'lucide-react';
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
    account_id: number | null;
    code: string;
    name: string;
    category: string;
    category_label: string;
    debit: number;
    credit: number;
    amount: number;
    is_computed?: boolean;
};

type AccountGroup = {
    key: string;
    label: string;
    lines: AccountLine[];
    total: number;
};

type Section = {
    key: string;
    title: string;
    groups: AccountGroup[];
    total: number;
};

type Summary = {
    total_assets: number;
    total_liabilities: number;
    total_equity: number;
    net_income: number;
    total_liabilities_and_equity: number;
    difference: number;
    is_balanced: boolean;
    assets_count: number;
    liabilities_count: number;
    equity_count: number;
};

type Report = {
    sections: Section[];
    summary: Summary;
};

type Props = {
    report: Report;
    filters: { as_of: string };
};

export default function BalanceSheetReport({ report, filters }: Props) {
    const { t } = useI18n();
    const [asOf, setAsOf] = useState(filters.as_of);
    const { summary, sections } = report;

    const assetsSection = sections.find((s) => s.key === 'assets');
    const liabilitiesSection = sections.find((s) => s.key === 'liabilities');
    const equitySection = sections.find((s) => s.key === 'equity');

    const applyFilters = () => {
        router.get(
            '/admin/accounting/reports/balance-sheet',
            { as_of: asOf },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={t('admin.reports.balance_sheet.title')} />
            <PageHeader
                title={t('admin.reports.balance_sheet.title')}
                description={t('admin.reports.balance_sheet.description').replace(
                    ':date',
                    formatDate(filters.as_of),
                )}
            />

            <Card className="mb-6 max-w-xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('admin.reports.balance_sheet.report_date')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap items-end gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="as_of">{t('admin.reports.balance_sheet.as_of_date')}</Label>
                        <Input
                            id="as_of"
                            type="date"
                            value={asOf}
                            onChange={(e) => setAsOf(e.target.value)}
                        />
                    </div>
                    <Button onClick={applyFilters}>{t('common.apply')}</Button>
                </CardContent>
            </Card>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.reports.balance_sheet.stat_assets')}
                    value={summary.total_assets}
                    format="currency"
                    icon={Landmark}
                    description={t('admin.reports.balance_sheet.accounts_count').replace(
                        ':count',
                        String(summary.assets_count),
                    )}
                />
                <StatCard
                    title={t('admin.reports.balance_sheet.stat_liabilities')}
                    value={summary.total_liabilities}
                    format="currency"
                    icon={Scale}
                    description={t('admin.reports.balance_sheet.accounts_count').replace(
                        ':count',
                        String(summary.liabilities_count),
                    )}
                />
                <StatCard
                    title={t('admin.reports.balance_sheet.stat_equity')}
                    value={summary.total_equity}
                    format="currency"
                    icon={TrendingUp}
                    description={
                        summary.net_income !== 0
                            ? t('admin.reports.balance_sheet.includes_income').replace(
                                  ':amount',
                                  formatCurrency(summary.net_income),
                              )
                            : t('admin.reports.balance_sheet.equity_accounts').replace(
                                  ':count',
                                  String(summary.equity_count),
                              )
                    }
                />
                <StatCard
                    title={t('admin.reports.balance_sheet.stat_difference')}
                    value={Math.abs(summary.difference)}
                    format="currency"
                    icon={ArrowLeftRight}
                    description={
                        summary.is_balanced
                            ? t('admin.reports.balance_sheet.balanced')
                            : t('admin.reports.balance_sheet.unbalanced')
                    }
                />
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant={summary.is_balanced ? 'default' : 'destructive'}>
                    {summary.is_balanced
                        ? t('admin.reports.balance_sheet.sheet_balanced')
                        : t('admin.reports.balance_sheet.sheet_unbalanced')}
                </Badge>
                {!summary.is_balanced && (
                    <Badge variant="outline" className="gap-1">
                        <AlertCircle className="size-3" />
                        {t('admin.reports.balance_sheet.difference_badge').replace(
                            ':amount',
                            formatCurrency(summary.difference),
                        )}
                    </Badge>
                )}
                {summary.net_income !== 0 && (
                    <Badge variant="secondary">
                        {summary.net_income >= 0
                            ? t('admin.reports.balance_sheet.retained_earnings')
                            : t('admin.reports.balance_sheet.retained_losses')}
                        : {formatCurrency(Math.abs(summary.net_income))}
                    </Badge>
                )}
            </div>

            <div className="mb-6 grid gap-4 lg:grid-cols-2">
                <ComparisonCard
                    title={t('admin.reports.balance_sheet.comparison_assets')}
                    amount={summary.total_assets}
                    variant="assets"
                />
                <ComparisonCard
                    title={t('admin.reports.balance_sheet.comparison_liab_equity')}
                    amount={summary.total_liabilities_and_equity}
                    variant="liabilities"
                    breakdown={[
                        {
                            label: t('admin.reports.balance_sheet.label_liabilities'),
                            amount: summary.total_liabilities,
                        },
                        {
                            label: t('admin.reports.balance_sheet.label_equity'),
                            amount: summary.total_equity,
                        },
                    ]}
                />
            </div>

            <div className="grid gap-6">
                {assetsSection && (
                    <BalanceSection section={assetsSection} sectionTotal={summary.total_assets} />
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    {liabilitiesSection && (
                        <BalanceSection
                            section={liabilitiesSection}
                            sectionTotal={summary.total_liabilities}
                        />
                    )}
                    {equitySection && (
                        <BalanceSection
                            section={equitySection}
                            sectionTotal={summary.total_equity}
                        />
                    )}
                </div>

                <Card className="border-primary/30 bg-primary/5">
                    <CardContent className="grid gap-3 py-5 sm:grid-cols-3">
                        <TotalRow
                            label={t('admin.reports.balance_sheet.total_assets')}
                            amount={summary.total_assets}
                            bold
                        />
                        <TotalRow
                            label={t('admin.reports.balance_sheet.total_liabilities')}
                            amount={summary.total_liabilities}
                        />
                        <TotalRow
                            label={t('admin.reports.balance_sheet.total_equity')}
                            amount={summary.total_equity}
                        />
                        <div className="sm:col-span-3 border-t pt-3">
                            <TotalRow
                                label={t('admin.reports.balance_sheet.total_liab_equity')}
                                amount={summary.total_liabilities_and_equity}
                                bold
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="mt-6 flex flex-wrap gap-2 text-sm">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/accounting/reports/profit-loss">
                        {t('admin.reports.balance_sheet.link_pl')}
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/accounting/reports/trial-balance">
                        {t('admin.reports.balance_sheet.link_trial')}
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/chart-of-accounts">
                        {t('admin.reports.balance_sheet.link_coa')}
                    </Link>
                </Button>
            </div>
        </>
    );
}

function ComparisonCard({
    title,
    amount,
    variant,
    breakdown,
}: {
    title: string;
    amount: number;
    variant: 'assets' | 'liabilities';
    breakdown?: { label: string; amount: number }[];
}) {
    return (
        <Card className={variant === 'assets' ? 'border-blue-500/20' : 'border-orange-500/20'}>
            <CardHeader className="pb-2">
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-bold">{formatCurrency(amount)}</p>
                {breakdown && (
                    <div className="mt-3 space-y-1 text-sm text-muted-foreground">
                        {breakdown.map((item) => (
                            <div key={item.label} className="flex justify-between">
                                <span>{item.label}</span>
                                <span>{formatCurrency(item.amount)}</span>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function BalanceSection({
    section,
    sectionTotal,
}: {
    section: Section;
    sectionTotal: number;
}) {
    const { t } = useI18n();
    const hasLines = section.groups.some((group) => group.lines.length > 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle>{section.title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6 p-0 pb-4">
                {!hasLines ? (
                    <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                        {t('admin.reports.balance_sheet.empty_section').replace(
                            ':title',
                            section.title,
                        )}
                    </p>
                ) : (
                    section.groups.map((group) => (
                        <div key={group.key}>
                            <div className="flex items-center justify-between border-b bg-muted/40 px-4 py-2">
                                <span className="text-sm font-semibold">{group.label}</span>
                                <span className="text-sm font-medium">
                                    {formatCurrency(group.total)}
                                </span>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-right text-muted-foreground">
                                            <th className="px-4 py-2 font-medium">
                                                {t('admin.reports.balance_sheet.col_code')}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {t('admin.reports.balance_sheet.col_account')}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {t('admin.reports.balance_sheet.col_debit')}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {t('admin.reports.balance_sheet.col_credit')}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {t('admin.reports.balance_sheet.col_balance')}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {t('admin.reports.balance_sheet.col_percent')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {group.lines.map((line) => (
                                            <tr
                                                key={`${line.account_id ?? line.code}-${line.name}`}
                                                className="border-b hover:bg-muted/20"
                                            >
                                                <td className="px-4 py-2 font-mono">
                                                    {line.account_id && !line.is_computed ? (
                                                        <Link
                                                            href={`/admin/chart-of-accounts/${line.account_id}`}
                                                            className="hover:underline"
                                                        >
                                                            {line.code}
                                                        </Link>
                                                    ) : (
                                                        line.code
                                                    )}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {line.name}
                                                    {line.is_computed && (
                                                        <Badge variant="outline" className="ms-2">
                                                            {t('admin.reports.balance_sheet.computed')}
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {line.debit > 0
                                                        ? formatCurrency(line.debit)
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {line.credit > 0
                                                        ? formatCurrency(line.credit)
                                                        : '—'}
                                                </td>
                                                <td
                                                    className={`px-4 py-2 font-medium ${line.amount < 0 ? 'text-destructive' : ''}`}
                                                >
                                                    {formatCurrency(line.amount)}
                                                </td>
                                                <td className="px-4 py-2 text-muted-foreground">
                                                    {sectionTotal > 0
                                                        ? `${((Math.abs(line.amount) / sectionTotal) * 100).toFixed(1)}%`
                                                        : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ))
                )}
                <div className="mx-4 flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3 font-semibold">
                    <span>
                        {t('admin.reports.balance_sheet.section_total').replace(
                            ':title',
                            section.title,
                        )}
                    </span>
                    <span>{formatCurrency(section.total)}</span>
                </div>
            </CardContent>
        </Card>
    );
}

function TotalRow({
    label,
    amount,
    bold = false,
}: {
    label: string;
    amount: number;
    bold?: boolean;
}) {
    return (
        <div className={`flex items-center justify-between ${bold ? 'text-lg font-bold' : ''}`}>
            <span>{label}</span>
            <span>{formatCurrency(amount)}</span>
        </div>
    );
}
