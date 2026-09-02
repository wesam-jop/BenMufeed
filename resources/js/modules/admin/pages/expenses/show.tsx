import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Pencil, Receipt, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';

type ExpenseDetail = {
    id: number;
    category: string;
    description: string | null;
    amount: number;
    payment_method: string;
    payment_method_label: string;
    expense_date: string | null;
    user_name: string | null;
    account_name: string | null;
    account_code: string | null;
    cash_register_name: string | null;
    created_at: string | null;
};

type Props = {
    expense: ExpenseDetail;
};

export default function ExpensesShow({ expense }: Props) {
    return (
        <>
            <Head title={`مصروف ${expense.category}`} />
            <PageHeader
                title={expense.description || expense.category}
                description="تفاصيل المصروف المسجّل"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/expenses">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/admin/expenses/${expense.id}/edit`}>
                        <Pencil className="size-4" />
                        تعديل
                    </Link>
                </Button>
                <ResourceActions
                    deleteHref={`/admin/expenses/${expense.id}`}
                    deletePermission="expenses.delete"
                />
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant="outline">{expense.category}</Badge>
                <Badge>{expense.payment_method_label}</Badge>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2">
                <StatCard
                    title="المبلغ"
                    value={expense.amount}
                    format="currency"
                    icon={Wallet}
                />
                <StatCard
                    title="التاريخ"
                    value={expense.expense_date ?? '—'}
                    icon={Receipt}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>التفاصيل</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <DetailRow label="التصنيف" value={expense.category} />
                    <DetailRow label="طريقة الدفع" value={expense.payment_method_label} />
                    <DetailRow
                        label="الحساب"
                        value={
                            expense.account_name
                                ? `${expense.account_code ?? ''} ${expense.account_name}`.trim()
                                : null
                        }
                    />
                    <DetailRow label="الصندوق" value={expense.cash_register_name} />
                    <DetailRow label="بواسطة" value={expense.user_name} />
                    <DetailRow
                        label="تاريخ التسجيل"
                        value={expense.created_at ? formatDate(expense.created_at) : null}
                    />
                    {expense.description && (
                        <div className="sm:col-span-2">
                            <DetailRow label="الوصف" value={expense.description} />
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
