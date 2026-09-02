import { Head, Link, useForm } from '@inertiajs/react';
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

type ExpenseForm = {
    id: number;
    category: string;
    description: string | null;
    amount: number;
    payment_method: string;
    expense_date: string | null;
    account_id: number | null;
};

type Props = {
    expense: ExpenseForm;
    accounts: AccountOption[];
    paymentMethods: PaymentOption[];
    categories: string[];
};

export default function ExpensesEdit({
    expense,
    accounts,
    paymentMethods,
    categories,
}: Props) {
    const categoryOptions = categories.includes(expense.category)
        ? categories
        : [expense.category, ...categories];

    const { data, setData, put, processing, errors, transform } = useForm({
        category: expense.category,
        amount: expense.amount,
        account_id: expense.account_id ? String(expense.account_id) : '',
        payment_method: expense.payment_method,
        description: expense.description ?? '',
        expense_date: expense.expense_date ?? '',
    });

    transform((form) => ({
        ...form,
        account_id: form.account_id ? Number(form.account_id) : null,
    }));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/expenses/${expense.id}`);
    };

    return (
        <>
            <Head title="تعديل مصروف" />
            <PageHeader title="تعديل المصروف" description={expense.description ?? expense.category} />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>بيانات المصروف</CardTitle>
                    <CardDescription>
                        تعديل البيانات الأساسية — القيد المحاسبي السابق لا يُعاد إنشاؤه
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
                                        {categoryOptions.map((category) => (
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
                            <Label htmlFor="account_id">حساب المصروف</Label>
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
                            <Label htmlFor="description">الوصف</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/expenses/${expense.id}`}>إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
