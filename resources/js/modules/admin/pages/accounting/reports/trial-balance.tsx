import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeftRight,
    BookOpen,
    Filter,
    Landmark,
    Scale,
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

type TrialLine = {
    account_id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    debit: number;
    credit: number;
    balance: number;
    debit_balance: number;
    credit_balance: number;
    normal_side: 'debit' | 'credit';
};

type SectionSummary = {
    accounts_count: number;
    total_debit: number;
    total_credit: number;
    debit_balance: number;
    credit_balance: number;
};

type Section = {
    key: string;
    title: string;
    lines: TrialLine[];
    summary: SectionSummary;
};

type Summary = {
    accounts_count: number;
    total_debit: number;
    total_credit: number;
    total_debit_balance: number;
    total_credit_balance: number;
    difference: number;
    is_balanced: boolean;
    by_type: Record<string, number>;
};

type Report = {
    sections: Section[];
    lines: TrialLine[];
    summary: Summary;
};

type Props = {
    report: Report;
    filters: { as_of: string };
};

export default function TrialBalanceReport({ report, filters }: Props) {
    const { t } = useI18n();
    const [asOf, setAsOf] = useState(filters.as_of);
    const { summary, sections, lines } = report;

    const applyFilters = () => {
        router.get(
            '/admin/accounting/reports/trial-balance',
            { as_of: asOf },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={t('admin.reports.trial_balance.title')} />
            <PageHeader
                title={t('admin.reports.trial_balance.title')}
                description={t('admin.reports.trial_balance.description').replace(
                    ':date',
                    formatDate(filters.as_of),
                )}
            />

            <Card className="mb-6 max-w-xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('admin.reports.trial_balance.report_date')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap items-end gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="as_of">{t('admin.reports.trial_balance.as_of_date')}</Label>
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
                    title={t('admin.reports.trial_balance.stat_accounts')}
                    value={summary.accounts_count}
                    format="number"
                    icon={BookOpen}
                    description={t('admin.reports.trial_balance.types_count').replace(
                        ':count',
                        String(sections.length),
                    )}
                />
                <StatCard
                    title={t('admin.reports.trial_balance.stat_debit')}
                    value={summary.total_debit}
                    format="currency"
                    icon={Landmark}
                />
                <StatCard
                    title={t('admin.reports.trial_balance.stat_credit')}
                    value={summary.total_credit}
                    format="currency"
                    icon={Scale}
                />
                <StatCard
                    title={t('admin.reports.trial_balance.stat_difference')}
                    value={Math.abs(summary.difference)}
                    format="currency"
                    icon={ArrowLeftRight}
                    description={
                        summary.is_balanced
                            ? t('admin.reports.trial_balance.balanced')
                            : t('admin.reports.trial_balance.unbalanced')
                    }
                />
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant={summary.is_balanced ? 'default' : 'destructive'}>
                    {summary.is_balanced
                        ? t('admin.reports.trial_balance.trial_balanced')
                        : t('admin.reports.trial_balance.trial_unbalanced')}
                </Badge>
                {!summary.is_balanced && (
                    <Badge variant="outline" className="gap-1">
                        <AlertCircle className="size-3" />
                        {t('admin.reports.trial_balance.difference_badge').replace(
                            ':amount',
                            formatCurrency(summary.difference),
                        )}
                    </Badge>
                )}
            </div>

            <div className="mb-6 grid gap-4 lg:grid-cols-2">
                <ComparisonCard
                    title={t('admin.reports.trial_balance.debit_movements')}
                    amount={summary.total_debit}
                    subtitle={t('admin.reports.trial_balance.debit_movements_sub')}
                />
                <ComparisonCard
                    title={t('admin.reports.trial_balance.credit_movements')}
                    amount={summary.total_credit}
                    subtitle={t('admin.reports.trial_balance.credit_movements_sub')}
                />
            </div>

            {lines.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <Scale className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.reports.trial_balance.empty')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.reports.trial_balance.empty_hint')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/journal-entries/create">
                                {t('admin.reports.trial_balance.new_entry')}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-6">
                    {sections.map((section) => (
                        <TrialSection key={section.key} section={section} />
                    ))}

                    <Card className="border-primary/30 bg-primary/5">
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-right">
                                            <th className="px-4 py-3 font-semibold" colSpan={3}>
                                                {t('admin.reports.trial_balance.grand_total')}
                                            </th>
                                            <th className="px-4 py-3 font-semibold">
                                                {t('admin.reports.trial_balance.col_debit')}
                                            </th>
                                            <th className="px-4 py-3 font-semibold">
                                                {t('admin.reports.trial_balance.col_credit')}
                                            </th>
                                            <th className="px-4 py-3 font-semibold">
                                                {t('admin.reports.trial_balance.col_debit_bal')}
                                            </th>
                                            <th className="px-4 py-3 font-semibold">
                                                {t('admin.reports.trial_balance.col_credit_bal')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr className="font-bold">
                                            <td className="px-4 py-4" colSpan={3}>
                                                {t('admin.reports.trial_balance.accounts_count').replace(
                                                    ':count',
                                                    String(summary.accounts_count),
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                {formatCurrency(summary.total_debit)}
                                            </td>
                                            <td className="px-4 py-4">
                                                {formatCurrency(summary.total_credit)}
                                            </td>
                                            <td className="px-4 py-4">
                                                {formatCurrency(summary.total_debit_balance)}
                                            </td>
                                            <td className="px-4 py-4">
                                                {formatCurrency(summary.total_credit_balance)}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            <div className="mt-6 flex flex-wrap gap-2 text-sm">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/accounting/reports/profit-loss">
                        {t('admin.reports.trial_balance.link_pl')}
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/accounting/reports/balance-sheet">
                        {t('admin.reports.trial_balance.link_bs')}
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/chart-of-accounts">
                        {t('admin.reports.trial_balance.link_coa')}
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/journal-entries">
                        {t('admin.reports.trial_balance.link_journal')}
                    </Link>
                </Button>
            </div>
        </>
    );
}

function ComparisonCard({
    title,
    amount,
    subtitle,
}: {
    title: string;
    amount: number;
    subtitle: string;
}) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-bold">{formatCurrency(amount)}</p>
                <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>
            </CardContent>
        </Card>
    );
}

function TrialSection({ section }: { section: Section }) {
    const { t } = useI18n();

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-4">
                <CardTitle>{section.title}</CardTitle>
                <Badge variant="secondary">
                    {t('admin.reports.trial_balance.section_accounts').replace(
                        ':count',
                        String(section.summary.accounts_count),
                    )}
                </Badge>
            </CardHeader>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50 text-right">
                                <th className="px-4 py-3 font-medium">
                                    {t('admin.reports.trial_balance.col_code')}
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    {t('admin.reports.trial_balance.col_account')}
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    {t('admin.reports.trial_balance.col_nature')}
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    {t('admin.reports.trial_balance.col_debit')}
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    {t('admin.reports.trial_balance.col_credit')}
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    {t('admin.reports.trial_balance.col_debit_bal')}
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    {t('admin.reports.trial_balance.col_credit_bal')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {section.lines.map((line) => (
                                <tr key={line.account_id} className="border-b hover:bg-muted/20">
                                    <td className="px-4 py-3 font-mono">
                                        <Link
                                            href={`/admin/chart-of-accounts/${line.account_id}`}
                                            className="hover:underline"
                                        >
                                            {line.code}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{line.name}</td>
                                    <td className="px-4 py-3">
                                        <Badge variant="outline">
                                            {line.normal_side === 'debit'
                                                ? t('admin.reports.trial_balance.nature_debit')
                                                : t('admin.reports.trial_balance.nature_credit')}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        {line.debit > 0 ? formatCurrency(line.debit) : '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {line.credit > 0 ? formatCurrency(line.credit) : '—'}
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        {line.debit_balance > 0
                                            ? formatCurrency(line.debit_balance)
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        {line.credit_balance > 0
                                            ? formatCurrency(line.credit_balance)
                                            : '—'}
                                    </td>
                                </tr>
                            ))}
                            <tr className="border-t bg-muted/30 font-semibold">
                                <td className="px-4 py-3" colSpan={3}>
                                    {t('admin.reports.trial_balance.section_total').replace(
                                        ':title',
                                        section.title,
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    {formatCurrency(section.summary.total_debit)}
                                </td>
                                <td className="px-4 py-3">
                                    {formatCurrency(section.summary.total_credit)}
                                </td>
                                <td className="px-4 py-3">
                                    {formatCurrency(section.summary.debit_balance)}
                                </td>
                                <td className="px-4 py-3">
                                    {formatCurrency(section.summary.credit_balance)}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
