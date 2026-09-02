import { Head, Link, useForm } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type AccountOption = { id: number; name: string; code: string };
type PaymentOption = { value: string; label: string };

type Props = {
    accounts: AccountOption[];
    paymentMethods: PaymentOption[];
    categories: string[];
    defaultDate: string;
};

export default function ExpensesCreate({
    accounts,
    paymentMethods,
    categories,
    defaultDate,
}: Props) {
    const { data, setData, post, processing, errors, transform } = useForm({
        category: categories[0] ?? 'تشغيل',
        amount: 0,
        account_id: accounts[0] ? String(accounts[0].id) : '',
        payment_method: 'cash',
        description: '',
        expense_date: defaultDate,
    });

    transform((form) => ({
        ...form,
        account_id: form.account_id ? Number(form.account_id) : null,
    }));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/expenses');
    };

    return (
        <>
            <Head title="مصروف جديد" />
            <PageHeader
                title="تسجيل مصروف"
                description="إذا كان الدفع نقدياً يُخصم من جلسة الصندوق المفتوحة تلقائياً"
            />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Receipt className="size-5" />
                        بيانات المصروف
                    </CardTitle>
                    <CardDescription>
                        التصنيف والمبلغ وطريقة الدفع إلزامية — الوصف والحساب اختياريان
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="category">التصنيف *</Label>
                                <Select
                                    value={data.category}
                                    onValueChange={(value) => setData('category', value)}
                                >
                                    <SelectTrigger id="category" className="h-11 w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category} value={category}>
                                                {category}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.category} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="amount">المبلغ *</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    min="0.01"
                                    step="any"
                                    value={String(data.amount)}
                                    onChange={(e) => setData('amount', Number(e.target.value))}
                                    required
                                />
                                <InputError message={errors.amount} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="payment_method">طريقة الدفع *</Label>
                                <Select
                                    value={data.payment_method}
                                    onValueChange={(value) => setData('payment_method', value)}
                                >
                                    <SelectTrigger id="payment_method" className="h-11 w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {paymentMethods.map((method) => (
                                            <SelectItem key={method.value} value={method.value}>
                                                {method.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.payment_method} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="expense_date">التاريخ *</Label>
                                <Input
                                    id="expense_date"
                                    type="date"
                                    value={data.expense_date}
                                    onChange={(e) => setData('expense_date', e.target.value)}
                                    required
                                />
                                <InputError message={errors.expense_date} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="account_id">
                                حساب المصروف{' '}
                                <span className="text-xs text-muted-foreground">(اختياري)</span>
                            </Label>
                            <Select
                                value={data.account_id || '__none__'}
                                onValueChange={(value) =>
                                    setData('account_id', value === '__none__' ? '' : value)
                                }
                            >
                                <SelectTrigger id="account_id" className="h-11 w-full">
                                    <SelectValue placeholder="اختر الحساب" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">بدون تحديد</SelectItem>
                                    {accounts.map((account) => (
                                        <SelectItem key={account.id} value={String(account.id)}>
                                            {account.code} — {account.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.account_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">
                                الوصف{' '}
                                <span className="text-xs text-muted-foreground">(اختياري)</span>
                            </Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="مثال: فاتورة كهرباء الشهر"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    !data.category ||
                                    !data.expense_date ||
                                    Number(data.amount) <= 0
                                }
                            >
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/expenses">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
