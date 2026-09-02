import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, Pencil, Receipt, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';

type ChildAccount = {
    id: number;
    code: string;
    name: string;
    type_label: string;
    is_active: boolean;
    journal_lines_count: number;
};

type JournalLine = {
    id: number;
    entry_number: string | null;
    date: string | null;
    description: string | null;
    debit: number;
    credit: number;
};

type AccountDetail = {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    parent_code: string | null;
    parent_name: string | null;
    parent_id: number | null;
    is_system: boolean;
    is_active: boolean;
    children_count: number;
    journal_lines_count: number;
    expenses_count: number;
    debit: number;
    credit: number;
    balance: number;
    children: ChildAccount[];
};

type Props = {
    account: AccountDetail;
    recentLines: JournalLine[];
};

export default function ChartOfAccountsShow({ account, recentLines }: Props) {
    return (
        <>
            <Head title={`${account.code} — ${account.name}`} />
            <PageHeader
                title={`${account.code} — ${account.name}`}
                description="تفاصيل الحساب وحركاته المحاسبية"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/chart-of-accounts">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/admin/chart-of-accounts/${account.id}/edit`}>
                        <Pencil className="size-4" />
                        تعديل
                    </Link>
                </Button>
                {!account.is_system && (
                    <ResourceActions
                        deleteHref={`/admin/chart-of-accounts/${account.id}`}
                        deletePermission="chart-of-accounts.delete"
                    />
                )}
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant="secondary">{account.type_label}</Badge>
                <Badge variant={account.is_active ? 'default' : 'outline'}>
                    {account.is_active ? 'نشط' : 'موقوف'}
                </Badge>
                {account.is_system && <Badge variant="outline">نظامي</Badge>}
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title="الرصيد"
                    value={account.balance}
                    format="currency"
                    icon={Wallet}
                    description={`مدين ${formatCurrency(account.debit)} · دائن ${formatCurrency(account.credit)}`}
                />
                <StatCard
                    title="قيود يومية"
                    value={account.journal_lines_count}
                    format="number"
                    icon={BookOpen}
                />
                <StatCard
                    title="مصروفات"
                    value={account.expenses_count}
                    format="number"
                    icon={Receipt}
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>معلومات الحساب</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <DetailRow label="الرمز" value={account.code} />
                        <DetailRow label="النوع" value={account.type_label} />
                        <DetailRow
                            label="الحساب الأب"
                            value={
                                account.parent_name
                                    ? `${account.parent_code} — ${account.parent_name}`
                                    : null
                            }
                        />
                        <DetailRow
                            label="حسابات فرعية"
                            value={
                                account.children_count > 0
                                    ? String(account.children_count)
                                    : null
                            }
                        />
                    </CardContent>
                </Card>

                {account.children.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>الحسابات الفرعية</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50 text-right">
                                            <th className="px-4 py-3 font-medium">الرمز</th>
                                            <th className="px-4 py-3 font-medium">الاسم</th>
                                            <th className="px-4 py-3 font-medium">النوع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {account.children.map((child) => (
                                            <tr key={child.id} className="border-b">
                                                <td className="px-4 py-3 font-mono">{child.code}</td>
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/admin/chart-of-accounts/${child.id}`}
                                                        className="hover:underline"
                                                    >
                                                        {child.name}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">{child.type_label}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>آخر الحركات المحاسبية</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {recentLines.length === 0 ? (
                        <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                            لا توجد حركات مسجّلة على هذا الحساب بعد
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">التاريخ</th>
                                        <th className="px-4 py-3 font-medium">رقم القيد</th>
                                        <th className="px-4 py-3 font-medium">البيان</th>
                                        <th className="px-4 py-3 font-medium">مدين</th>
                                        <th className="px-4 py-3 font-medium">دائن</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentLines.map((line) => (
                                        <tr key={line.id} className="border-b">
                                            <td className="px-4 py-3">
                                                {line.date ? formatDate(line.date) : '—'}
                                            </td>
                                            <td className="px-4 py-3 font-mono">
                                                {line.entry_number ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">{line.description ?? '—'}</td>
                                            <td className="px-4 py-3">
                                                {line.debit > 0 ? formatCurrency(line.debit) : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {line.credit > 0 ? formatCurrency(line.credit) : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

function DetailRow({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="space-y-1">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="font-medium">{value ?? '—'}</p>
        </div>
    );
}
