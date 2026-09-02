import { Head, Link, useForm } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
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

type CashRegisterOption = { id: number; name: string };

type Props = {
    cashRegisters: CashRegisterOption[];
};

export default function CashRegisterSessionsCreate({ cashRegisters }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        cash_register_id: '',
        opening_balance: 0,
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/cash-register-sessions');
    };

    return (
        <>
            <Head title="فتح جلسة صندوق" />
            <PageHeader
                title="فتح جلسة جديدة"
                description="اختر الصندوق والرصيد الافتتاحي لبدء يوم الكاشير"
            />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CreditCard className="size-5" />
                        بيانات الجلسة
                    </CardTitle>
                    <CardDescription>
                        تُفتح الجلسة أيضاً تلقائياً عند أول بيع من نقطة البيع
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="cash_register_id">الصندوق *</Label>
                            <Select
                                value={data.cash_register_id}
                                onValueChange={(value) => setData('cash_register_id', value)}
                                required
                            >
                                <SelectTrigger id="cash_register_id" className="h-11 w-full">
                                    <SelectValue placeholder="اختر الصندوق" />
                                </SelectTrigger>
                                <SelectContent>
                                    {cashRegisters.map((register) => (
                                        <SelectItem
                                            key={register.id}
                                            value={String(register.id)}
                                        >
                                            {register.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.cash_register_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="opening_balance">الرصيد الافتتاحي *</Label>
                            <Input
                                id="opening_balance"
                                type="number"
                                min="0"
                                step="any"
                                value={String(data.opening_balance)}
                                onChange={(e) =>
                                    setData('opening_balance', Number(e.target.value))
                                }
                                required
                            />
                            <InputError message={errors.opening_balance} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">
                                ملاحظات{' '}
                                <span className="text-xs text-muted-foreground">(اختياري)</span>
                            </Label>
                            <Input
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder="ملاحظات الافتتاح"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button
                                type="submit"
                                disabled={processing || !data.cash_register_id}
                            >
                                {processing && <Spinner />}
                                فتح الجلسة
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/cash-register-sessions">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
