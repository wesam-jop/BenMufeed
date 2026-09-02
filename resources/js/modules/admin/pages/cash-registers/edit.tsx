import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type CashRegisterForm = {
    id: number;
    name: string;
    is_active: boolean;
};

type Props = {
    cashRegister: CashRegisterForm;
};

export default function CashRegistersEdit({ cashRegister }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: cashRegister.name,
        is_active: cashRegister.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/cash-registers/${cashRegister.id}`);
    };

    return (
        <>
            <Head title={`تعديل ${cashRegister.name}`} />
            <PageHeader title="تعديل الصندوق" description={cashRegister.name} />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle>بيانات الصندوق</CardTitle>
                    <CardDescription>
                        إيقاف الصندوق يمنعه من الظهور لنقطة البيع
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم الصندوق *</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked === true)
                                }
                            />
                            <Label htmlFor="is_active" className="font-normal">
                                صندوق نشط
                            </Label>
                        </div>
                        <InputError message={errors.is_active} />

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing || !data.name.trim()}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/cash-registers/${cashRegister.id}`}>
                                    إلغاء
                                </Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
