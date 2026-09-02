import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, CreditCard, Wallet } from 'lucide-react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency, formatDate } from '@/lib/format';

type MovementRow = {
    id: number;
    type: string;
    type_label: string;
    amount: number;
    notes: string | null;
    created_at: string | null;
};

type SessionDetail = {
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
    closed_at: string | null;
    notes: string | null;
    can_close: boolean;
    summary: {
        invoice_count: number;
        total_sales: number;
        cash_sales: number;
        card_sales: number;
    };
    movements: MovementRow[];
};

type Props = {
    cashRegisterSession: SessionDetail;
};

export default function CashRegisterSessionsShow({ cashRegisterSession }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        actual_cash: cashRegisterSession.expected_cash,
        notes: '',
    });

    const closeSession = (e: React.FormEvent) => {
        e.preventDefault();

        if (
            !confirm(
                'إغلاق الجلسة يثبت النقد الفعلي والفرق. هل تريد المتابعة؟',
            )
        ) {
            return;
        }

        post(`/admin/cash-register-sessions/${cashRegisterSession.id}/close`);
    };

    return (
        <>
            <Head title={`جلسة ${cashRegisterSession.cash_register_name}`} />
            <PageHeader
                title={cashRegisterSession.cash_register_name}
                description="تفاصيل جلسة الكاشير والمبيعات وحركات النقد"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/cash-register-sessions">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                {cashRegisterSession.can_close && (
                    <ResourceActions
                        deleteHref={`/admin/cash-register-sessions/${cashRegisterSession.id}`}
                        deletePermission="cash-register-sessions.delete"
                    />
                )}
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge
                    variant={cashRegisterSession.status === 'open' ? 'default' : 'secondary'}
                >
                    {cashRegisterSession.status_label}
                </Badge>
                {cashRegisterSession.user_name && (
                    <Badge variant="outline">{cashRegisterSession.user_name}</Badge>
                )}
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title="افتتاحي"
                    value={cashRegisterSession.opening_balance}
                    format="currency"
                    icon={Wallet}
                />
                <StatCard
                    title="متوقع"
                    value={cashRegisterSession.expected_cash}
                    format="currency"
                />
                <StatCard
                    title="مبيعات اليوم"
                    value={cashRegisterSession.summary.total_sales}
                    format="currency"
                    icon={CreditCard}
                    description={`${cashRegisterSession.summary.invoice_count} فاتورة`}
                />
                <StatCard
                    title="الفرق"
                    value={
                        cashRegisterSession.difference !== null
                            ? cashRegisterSession.difference
                            : '—'
                    }
                    format={cashRegisterSession.difference !== null ? 'currency' : 'text'}
                />
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">تفصيل المبيعات</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">نقدي</span>
                            <span>{formatCurrency(cashRegisterSession.summary.cash_sales)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">بطاقة / أخرى</span>
                            <span>{formatCurrency(cashRegisterSession.summary.card_sales)}</span>
                        </div>
                        <div className="flex justify-between border-t pt-2 font-medium">
                            <span>الإجمالي</span>
                            <span>
                                {formatCurrency(cashRegisterSession.summary.total_sales)}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">التوقيت</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">الفتح</span>
                            <span>
                                {cashRegisterSession.opened_at
                                    ? formatDate(cashRegisterSession.opened_at)
                                    : '—'}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">الإغلاق</span>
                            <span>
                                {cashRegisterSession.closed_at
                                    ? formatDate(cashRegisterSession.closed_at)
                                    : '—'}
                            </span>
                        </div>
                        {cashRegisterSession.actual_cash !== null && (
                            <div className="flex justify-between border-t pt-2">
                                <span className="text-muted-foreground">النقد الفعلي</span>
                                <span>
                                    {formatCurrency(cashRegisterSession.actual_cash)}
                                </span>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {cashRegisterSession.can_close && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>إغلاق الجلسة</CardTitle>
                        <CardDescription>
                            أدخل المبلغ الفعلي في الصندوق لمقارنة الفرق مع المتوقع
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={closeSession} className="grid max-w-md gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="actual_cash">النقد الفعلي *</Label>
                                <Input
                                    id="actual_cash"
                                    type="number"
                                    min="0"
                                    step="any"
                                    value={String(data.actual_cash)}
                                    onChange={(e) =>
                                        setData('actual_cash', Number(e.target.value))
                                    }
                                    required
                                />
                                <InputError message={errors.actual_cash} />
                                <p className="text-xs text-muted-foreground">
                                    المتوقع حالياً:{' '}
                                    {formatCurrency(cashRegisterSession.expected_cash)}
                                </p>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="notes">ملاحظات الإغلاق</Label>
                                <Input
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                إغلاق الجلسة
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>حركات النقد</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {cashRegisterSession.movements.length === 0 ? (
                        <div className="px-6 py-12 text-center text-muted-foreground">
                            لا توجد حركات نقد بعد
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">النوع</th>
                                        <th className="px-4 py-3 font-medium">المبلغ</th>
                                        <th className="px-4 py-3 font-medium">ملاحظات</th>
                                        <th className="px-4 py-3 font-medium">الوقت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {cashRegisterSession.movements.map((movement) => (
                                        <tr key={movement.id} className="border-b">
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">
                                                    {movement.type_label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(movement.amount)}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {movement.notes ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {movement.created_at
                                                    ? formatDate(movement.created_at)
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
