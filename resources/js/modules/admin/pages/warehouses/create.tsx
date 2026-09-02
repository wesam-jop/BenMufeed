import { Head, Link, useForm } from '@inertiajs/react';
import { Warehouse as WarehouseIcon } from 'lucide-react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

type TypeOption = { value: string; label: string };

type Props = {
    typeOptions: TypeOption[];
};

export default function WarehousesCreate({ typeOptions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        type: 'main',
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/warehouses');
    };

    return (
        <>
            <Head title="مستودع جديد" />
            <PageHeader
                title="إضافة مستودع"
                description="مكان تخزين داخل الفرع — يُستخدم في الجرد والتحويلات والهدر"
            />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <WarehouseIcon className="size-5" />
                        بيانات المستودع
                    </CardTitle>
                    <CardDescription>
                        مثال: المستودع الرئيسي، مستودع المطبخ، غرفة التبريد
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم المستودع *</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="مثال: المستودع الرئيسي"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="type">نوع المستودع *</Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) => setData('type', value)}
                            >
                                <SelectTrigger id="type" className="h-11 w-full">
                                    <SelectValue placeholder="اختر النوع" />
                                </SelectTrigger>
                                <SelectContent>
                                    {typeOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.type} />
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
                                مستودع نشط (يظهر في عمليات المخزون)
                            </Label>
                        </div>
                        <InputError message={errors.is_active} />

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing || !data.name}>
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/warehouses">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
