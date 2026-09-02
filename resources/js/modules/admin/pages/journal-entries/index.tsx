import { Head, Link } from '@inertiajs/react';
import { BookOpen, Eye, FileText, Landmark } from 'lucide-react';
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
    journalEntryStatusLabel,
    journalEntryStatusVariant,
} from '@/lib/format';
import type { Paginated } from '@/types/admin';

type EntryRow = {
    id: number;
    entry_number: string;
    date: string | null;
    description: string | null;
    status: string;
    status_label: string;
    user_name: string | null;
    lines_count: number;
    total_debit: number;
    total_credit: number;
    reference_label: string | null;
    reference_url: string | null;
    is_manual: boolean;
    can_edit: boolean;
    can_delete: boolean;
};

type Summary = {
    entries_count: number;
    posted_count: number;
    manual_count: number;
    system_count: number;
    total_debit: number;
    total_credit: number;
    this_month_count: number;
};

type Props = {
    entries: Paginated<EntryRow>;
    summary: Summary;
};

export default function JournalEntriesIndex({ entries, summary }: Props) {
    const { t } = useI18n();
    const items = entries.data ?? [];

    return (
        <>
            <Head title={t('admin.journal_entries.title')} />
            <PageHeader
                title={t('admin.journal_entries.title')}
                description={t('admin.journal_entries.description')}
                createHref="/admin/journal-entries/create"
                createLabel={t('admin.journal_entries.new')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.journal_entries.stat_count')}
                    value={summary.entries_count}
                    format="number"
                    icon={FileText}
                    description={t('admin.journal_entries.stat_count_desc')
                        .replace(':month', String(summary.this_month_count))
                        .replace(':posted', String(summary.posted_count))}
                />
                <StatCard
                    title={t('admin.journal_entries.stat_manual')}
                    value={summary.manual_count}
                    format="number"
                    icon={BookOpen}
                    description={t('admin.journal_entries.stat_system_desc').replace(
                        ':count',
                        String(summary.system_count),
                    )}
                />
                <StatCard
                    title={t('admin.journal_entries.stat_debit')}
                    value={summary.total_debit}
                    format="currency"
                    icon={Landmark}
                    description={t('admin.journal_entries.stat_debit_desc')}
                />
            </div>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <FileText className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.journal_entries.empty')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.journal_entries.empty_hint')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/journal-entries/create">
                                {t('admin.journal_entries.new')}
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
                                            {t('admin.journal_entries.col_number')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.journal_entries.col_description')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('common.amount')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.journal_entries.col_source')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-mono">
                                                <Link
                                                    href={`/admin/journal-entries/${item.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {item.entry_number}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.date ? formatDate(item.date) : '—'}
                                            </td>
                                            <td className="px-4 py-3 max-w-xs truncate">
                                                {item.description ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(item.total_debit)}
                                                <span className="ms-1 text-xs text-muted-foreground">
                                                    (
                                                    {t('admin.journal_entries.lines_count').replace(
                                                        ':count',
                                                        String(item.lines_count),
                                                    )}
                                                    )
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {item.reference_url && item.reference_label ? (
                                                    <Link
                                                        href={item.reference_url}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {item.reference_label}
                                                    </Link>
                                                ) : item.is_manual ? (
                                                    <Badge variant="outline">
                                                        {t('admin.journal_entries.manual')}
                                                    </Badge>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={journalEntryStatusVariant(item.status)}
                                                >
                                                    {item.status_label ||
                                                        journalEntryStatusLabel(item.status)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-1">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link
                                                            href={`/admin/journal-entries/${item.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={
                                                            item.can_edit
                                                                ? `/admin/journal-entries/${item.id}/edit`
                                                                : undefined
                                                        }
                                                        deleteHref={
                                                            item.can_delete
                                                                ? `/admin/journal-entries/${item.id}`
                                                                : undefined
                                                        }
                                                        editPermission="journal-entries.update"
                                                        deletePermission="journal-entries.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={entries} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
