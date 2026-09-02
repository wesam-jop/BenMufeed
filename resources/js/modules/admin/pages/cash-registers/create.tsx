import { Head, Link, useForm } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function CashRegistersCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/cash-registers');
    };

    return (
        <>
            <Head title="صندوق جديد" />
            <PageHeader
                title="إضافة صندوق"
                description="نقطة كاشير في الفرع — نقطة البيع تستخدم الصندوق النشط"
            />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CreditCard className="size-5" />
                        بيانات الصندوق
                    </CardTitle>
                    <CardDescription>
                        مثال: الصندوق الرئيسي، صندوق البار، صندوق التوصيل
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
                                placeholder="مثال: الصندوق الرئيسي"
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
                                صندوق نشط (يظهر لنقطة البيع)
                            </Label>
                        </div>
                        <InputError message={errors.is_active} />

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing || !data.name.trim()}>
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/cash-registers">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
