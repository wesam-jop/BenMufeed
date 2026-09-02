import { Head, Link } from '@inertiajs/react';
import { ArrowRight, FileText, Landmark, Pencil, User } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatCurrency,
    formatDate,
    journalEntryStatusLabel,
    journalEntryStatusVariant,
} from '@/lib/format';

type LineDetail = {
    id: number;
    account_id: number;
    account_code: string | null;
    account_name: string | null;
    debit: number;
    credit: number;
    description: string | null;
};

type EntryDetail = {
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
    lines: LineDetail[];
};

type Props = {
    entry: EntryDetail;
};

export default function JournalEntriesShow({ entry }: Props) {
    return (
        <>
            <Head title={entry.entry_number} />
            <PageHeader
                title={entry.entry_number}
                description={entry.description ?? 'تفاصيل القيد المحاسبي'}
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/journal-entries">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                {entry.can_edit && (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/admin/journal-entries/${entry.id}/edit`}>
                            <Pencil className="size-4" />
                            تعديل
                        </Link>
                    </Button>
                )}
                {entry.can_delete && (
                    <ResourceActions
                        deleteHref={`/admin/journal-entries/${entry.id}`}
                        deletePermission="journal-entries.delete"
                    />
                )}
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant={journalEntryStatusVariant(entry.status)}>
                    {entry.status_label || journalEntryStatusLabel(entry.status)}
                </Badge>
                {entry.is_manual ? (
                    <Badge variant="outline">قيد يدوي</Badge>
                ) : (
                    entry.reference_url &&
                    entry.reference_label && (
                        <Badge variant="secondary" asChild>
                            <Link href={entry.reference_url}>{entry.reference_label}</Link>
                        </Badge>
                    )
                )}
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title="إجمالي المدين"
                    value={entry.total_debit}
                    format="currency"
                    icon={Landmark}
                />
                <StatCard
                    title="إجمالي الدائن"
                    value={entry.total_credit}
                    format="currency"
                    icon={Landmark}
                />
                <StatCard
                    title="عدد السطور"
                    value={entry.lines_count}
                    format="number"
                    icon={FileText}
                />
                <StatCard
                    title="التاريخ"
                    value={entry.date ? formatDate(entry.date) : '—'}
                    icon={User}
                    description={entry.user_name ? `بواسطة ${entry.user_name}` : undefined}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>سطور القيد</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">الرمز</th>
                                    <th className="px-4 py-3 font-medium">الحساب</th>
                                    <th className="px-4 py-3 font-medium">مدين</th>
                                    <th className="px-4 py-3 font-medium">دائن</th>
                                    <th className="px-4 py-3 font-medium">البيان</th>
                                </tr>
                            </thead>
                            <tbody>
                                {entry.lines.map((line) => (
                                    <tr key={line.id} className="border-b">
                                        <td className="px-4 py-3 font-mono">
                                            {line.account_code ? (
                                                <Link
                                                    href={`/admin/chart-of-accounts/${line.account_id}`}
                                                    className="hover:underline"
                                                >
                                                    {line.account_code}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-4 py-3">{line.account_name ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            {line.debit > 0 ? formatCurrency(line.debit) : '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {line.credit > 0 ? formatCurrency(line.credit) : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {line.description ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t bg-muted/30 font-medium">
                                    <td className="px-4 py-3" colSpan={2}>
                                        المجموع
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatCurrency(entry.total_debit)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatCurrency(entry.total_credit)}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge>متوازن</Badge>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
