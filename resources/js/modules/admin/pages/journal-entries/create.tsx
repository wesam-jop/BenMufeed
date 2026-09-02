import { Head, Link, useForm } from '@inertiajs/react';
import { AlertCircle, FileText, Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
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
import { formatCurrency } from '@/lib/format';

type AccountOption = { id: number; code: string; name: string; type_label: string };

type LineForm = {
    account_id: string;
    debit: number;
    credit: number;
    description: string;
};

type Props = {
    accounts: AccountOption[];
    defaultDate: string;
};

const emptyLine = (): LineForm => ({
    account_id: '',
    debit: 0,
    credit: 0,
    description: '',
});

export default function JournalEntriesCreate({ accounts, defaultDate }: Props) {
    const { data, setData, post, processing, errors, transform } = useForm<{
        date: string;
        description: string;
        lines: LineForm[];
    }>({
        date: defaultDate,
        description: '',
        lines: [emptyLine(), emptyLine()],
    });

    transform((formData) => ({
        date: formData.date,
        description: formData.description,
        lines: formData.lines
            .filter((line) => line.account_id)
            .map((line) => ({
                account_id: Number(line.account_id),
                debit: Number(line.debit) || 0,
                credit: Number(line.credit) || 0,
                description: line.description || null,
            })),
    }));

    const totalDebit = data.lines.reduce((sum, line) => sum + (Number(line.debit) || 0), 0);
    const totalCredit = data.lines.reduce((sum, line) => sum + (Number(line.credit) || 0), 0);
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) => {
        if (data.lines.length <= 2) {
            return;
        }
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );
    };

    const updateLine = (index: number, field: keyof LineForm, value: string | number) => {
        const lines = [...data.lines];
        lines[index] = { ...lines[index], [field]: value };
        setData('lines', lines);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/journal-entries');
    };

    return (
        <>
            <Head title="قيد جديد" />
            <PageHeader
                title="قيد يومية جديد"
                description="قيد محاسبي يدوي — مجموع المدين يجب أن يساوي مجموع الدائن"
            />

            <form onSubmit={submit} className="grid gap-6">
                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="size-5" />
                            بيانات القيد
                        </CardTitle>
                        <CardDescription>التاريخ والوصف إلزاميان</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="date">التاريخ *</Label>
                            <Input
                                id="date"
                                type="date"
                                value={data.date}
                                onChange={(e) => setData('date', e.target.value)}
                                required
                            />
                            <InputError message={errors.date} />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="description">الوصف *</Label>
                            <Input
                                id="description"
                                type="text"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="مثال: تسوية رصيد نقدي"
                                required
                            />
                            <InputError message={errors.description} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <div>
                            <CardTitle>سطور القيد</CardTitle>
                            <CardDescription>اختر الحساب وأدخل المبلغ مدين أو دائن</CardDescription>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={addLine}>
                            <Plus className="size-4" />
                            سطر
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <InputError message={errors.lines} />

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-3 py-2 font-medium">الحساب</th>
                                        <th className="px-3 py-2 font-medium">مدين</th>
                                        <th className="px-3 py-2 font-medium">دائن</th>
                                        <th className="px-3 py-2 font-medium">البيان</th>
                                        <th className="px-3 py-2 font-medium" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.lines.map((line, index) => (
                                        <tr key={index} className="border-b align-top">
                                            <td className="px-3 py-2 min-w-[220px]">
                                                <Select
                                                    value={line.account_id || '__none__'}
                                                    onValueChange={(value) =>
                                                        updateLine(
                                                            index,
                                                            'account_id',
                                                            value === '__none__' ? '' : value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="h-10 w-full">
                                                        <SelectValue placeholder="اختر حساباً" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="__none__">
                                                            اختر حساباً
                                                        </SelectItem>
                                                        {accounts.map((account) => (
                                                            <SelectItem
                                                                key={account.id}
                                                                value={String(account.id)}
                                                            >
                                                                {account.code} — {account.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={
                                                        (errors as Record<string, string>)[
                                                            `lines.${index}.account_id`
                                                        ]
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    step="0.01"
                                                    value={line.debit || ''}
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'debit',
                                                            e.target.value === ''
                                                                ? 0
                                                                : Number(e.target.value),
                                                        )
                                                    }
                                                    className="w-28"
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    step="0.01"
                                                    value={line.credit || ''}
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'credit',
                                                            e.target.value === ''
                                                                ? 0
                                                                : Number(e.target.value),
                                                        )
                                                    }
                                                    className="w-28"
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input
                                                    type="text"
                                                    value={line.description}
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'description',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="اختياري"
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => removeLine(index)}
                                                    disabled={data.lines.length <= 2}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="border-t bg-muted/30 font-medium">
                                        <td className="px-3 py-3">المجموع</td>
                                        <td className="px-3 py-3">{formatCurrency(totalDebit)}</td>
                                        <td className="px-3 py-3">{formatCurrency(totalCredit)}</td>
                                        <td className="px-3 py-3" colSpan={2}>
                                            {isBalanced ? (
                                                <Badge>متوازن</Badge>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 text-destructive">
                                                    <AlertCircle className="size-4" />
                                                    غير متوازن (
                                                    {formatCurrency(Math.abs(totalDebit - totalCredit))})
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex gap-2">
                    <Button
                        type="submit"
                        disabled={processing || !data.description || !isBalanced}
                    >
                        {processing && <Spinner />}
                        حفظ القيد
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/admin/journal-entries">إلغاء</Link>
                    </Button>
                </div>
            </form>
        </>
    );
}
