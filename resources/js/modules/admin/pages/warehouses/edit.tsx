import { Head, Link, useForm } from '@inertiajs/react';
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

type WarehouseForm = {
    id: number;
    name: string;
    type: string;
    is_active: boolean;
};

type TypeOption = { value: string; label: string };

type Props = {
    warehouse: WarehouseForm;
    typeOptions: TypeOption[];
};

export default function WarehousesEdit({ warehouse, typeOptions }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: warehouse.name,
        type: warehouse.type,
        is_active: warehouse.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/warehouses/${warehouse.id}`);
    };

    return (
        <>
            <Head title={`تعديل ${warehouse.name}`} />
            <PageHeader title="تعديل المستودع" description={warehouse.name} />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle>بيانات المستودع</CardTitle>
                    <CardDescription>
                        إيقاف المستودع يخفيه من قوائم الجرد والتحويلات والهدر
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
                                    <SelectValue />
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
                                مستودع نشط
                            </Label>
                        </div>
                        <InputError message={errors.is_active} />

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/warehouses/${warehouse.id}`}>إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
