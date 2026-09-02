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
import { cn } from '@/lib/utils';

type UnitOption = { id: number; name: string; abbreviation: string };

type Props = {
    units: UnitOption[];
};

export default function InventoryMaterialsCreate({ units }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        sku: '',
        unit_id: '',
        cost_price: 0,
        min_stock: 0,
        description: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/inventory/materials');
    };

    return (
        <>
            <Head title="إضافة مادة" />
            <PageHeader
                title="إضافة مادة للمخزون"
                description="أضف مادة خام لتستخدمها في تكلفة الأطباق"
            />
            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>بيانات المادة</CardTitle>
                    <CardDescription>مثال: دقيق، حليب، لحم، زيت...</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم المادة *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="unit_id">الوحدة</Label>
                                <Select
                                    value={data.unit_id}
                                    onValueChange={(value) => setData('unit_id', value)}
                                >
                                    <SelectTrigger id="unit_id">
                                        <SelectValue placeholder="اختر الوحدة" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {units.map((unit) => (
                                            <SelectItem key={unit.id} value={String(unit.id)}>
                                                {unit.name} ({unit.abbreviation})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.unit_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="cost_price">تكلفة الوحدة *</Label>
                                <Input
                                    id="cost_price"
                                    type="number"
                                    min="0"
                                    step="0.0001"
                                    value={String(data.cost_price)}
                                    onChange={(e) => setData('cost_price', Number(e.target.value))}
                                    required
                                />
                                <InputError message={errors.cost_price} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="sku">رمز SKU</Label>
                                <Input
                                    id="sku"
                                    value={data.sku}
                                    onChange={(e) => setData('sku', e.target.value)}
                                />
                                <InputError message={errors.sku} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="min_stock">حد التنبيه</Label>
                                <Input
                                    id="min_stock"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={String(data.min_stock)}
                                    onChange={(e) => setData('min_stock', Number(e.target.value))}
                                />
                                <InputError message={errors.min_stock} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">ملاحظات</Label>
                            <textarea
                                id="description"
                                rows={3}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className={cn(
                                    'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                    'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                )}
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                حفظ المادة
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/inventory/materials">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
