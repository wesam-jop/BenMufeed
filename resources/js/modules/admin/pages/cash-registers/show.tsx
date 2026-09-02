import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CreditCard, Pencil, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';

type SessionRow = {
    id: number;
    status: string;
    status_label: string;
    session_date: string | null;
    opening_balance: number;
    expected_cash: number;
    actual_cash: number | null;
    difference: number | null;
    user_name: string | null;
    opened_at: string | null;
    closed_at: string | null;
};

type CashRegisterDetail = {
    id: number;
    name: string;
    is_active: boolean;
    sessions_count: number;
    open_sessions_count: number;
    sessions: SessionRow[];
};

type Props = {
    cashRegister: CashRegisterDetail;
};

export default function CashRegistersShow({ cashRegister }: Props) {
    return (
        <>
            <Head title={cashRegister.name} />
            <PageHeader
                title={cashRegister.name}
                description="تفاصيل الصندوق وآخر جلسات الكاشير"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/cash-registers">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/admin/cash-registers/${cashRegister.id}/edit`}>
                        <Pencil className="size-4" />
                        تعديل
                    </Link>
                </Button>
                <ResourceActions
                    deleteHref={`/admin/cash-registers/${cashRegister.id}`}
                    deletePermission="cash-registers.delete"
                />
            </div>

            <div className="mb-6">
                <Badge variant={cashRegister.is_active ? 'default' : 'secondary'}>
                    {cashRegister.is_active ? 'نشط' : 'موقوف'}
                </Badge>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title="إجمالي الجلسات"
                    value={cashRegister.sessions_count}
                    format="number"
                    icon={CreditCard}
                />
                <StatCard
                    title="جلسات مفتوحة"
                    value={cashRegister.open_sessions_count}
                    format="number"
                    icon={Wallet}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>آخر الجلسات</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {cashRegister.sessions.length === 0 ? (
                        <div className="px-6 py-12 text-center text-muted-foreground">
                            لا توجد جلسات بعد — تُفتح تلقائياً عند البيع من نقطة البيع
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">التاريخ</th>
                                        <th className="px-4 py-3 font-medium">الحالة</th>
                                        <th className="px-4 py-3 font-medium">الكاشير</th>
                                        <th className="px-4 py-3 font-medium">افتتاحي</th>
                                        <th className="px-4 py-3 font-medium">المتوقع</th>
                                        <th className="px-4 py-3 font-medium">الفعلي</th>
                                        <th className="px-4 py-3 font-medium">الفرق</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {cashRegister.sessions.map((session) => (
                                        <tr
                                            key={session.id}
                                            className="border-b hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                {session.opened_at
                                                    ? formatDate(session.opened_at)
                                                    : session.session_date ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        session.status === 'open'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {session.status_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {session.user_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(session.opening_balance)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(session.expected_cash)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {session.actual_cash !== null
                                                    ? formatCurrency(session.actual_cash)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {session.difference !== null
                                                    ? formatCurrency(session.difference)
                                                    : '—'}
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
