import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, FileWarning } from 'lucide-react';
import InputError from '@/components/input-error';
import MaterialSearchSelect, {
    type MaterialSearchOption,
} from '@/components/admin/material-search-select';
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
import { formatCurrency, formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';

type WarehouseOption = { id: number; name: string };

type MaterialOption = MaterialSearchOption & {
    available_qty: number;
};

type ReasonOption = { value: string; label: string };

type Props = {
    warehouses: WarehouseOption[];
    materials: MaterialOption[];
    reasonOptions: ReasonOption[];
    selectedWarehouseId: number | null;
};

export default function WastageRecordsCreate({
    warehouses,
    materials,
    reasonOptions,
    selectedWarehouseId,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        warehouse_id: selectedWarehouseId ? String(selectedWarehouseId) : '',
        product_id: '',
        quantity: 1,
        reason: '',
        notes: '',
    });

    const selectedMaterial = materials.find(
        (material) => material.id === Number(data.product_id),
    );

    const lineValue =
        (Number(data.quantity) || 0) * (selectedMaterial?.cost_price ?? 0);

    const exceedsAvailable =
        selectedMaterial !== undefined &&
        Number(data.quantity) > selectedMaterial.available_qty;

    const handleWarehouseChange = (value: string) => {
        setData((current) => ({
            ...current,
            warehouse_id: value,
            product_id: '',
            quantity: 1,
        }));

        router.get(
            '/admin/wastage-records/create',
            { warehouse_id: value },
            { preserveState: true, replace: true, only: ['materials', 'selectedWarehouseId'] },
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/wastage-records');
    };

    const materialSearchOptions: MaterialSearchOption[] = materials.map((material) => ({
        id: material.id,
        name: material.name,
        cost_price: material.cost_price,
        unit: material.unit,
    }));

    return (
        <>
            <Head title="تسجيل هدر" />
            <PageHeader
                title="تسجيل هدر جديد"
                description="اختر المستودع والمادة — سيتم خصم الكمية من المخزون فور الحفظ"
            />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <FileWarning className="size-5 text-destructive" />
                        بيانات الهدر
                    </CardTitle>
                    <CardDescription>
                        تأكد من الكمية المتاحة قبل التسجيل — لا يمكن تعديل السجل بعد الخصم
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-5">
                        <div className="grid gap-2">
                            <Label htmlFor="warehouse_id">المستودع *</Label>
                            <Select
                                value={data.warehouse_id}
                                onValueChange={handleWarehouseChange}
                                required
                            >
                                <SelectTrigger id="warehouse_id" className="h-11 w-full">
                                    <SelectValue placeholder="اختر المستودع" />
                                </SelectTrigger>
                                <SelectContent>
                                    {warehouses.map((warehouse) => (
                                        <SelectItem
                                            key={warehouse.id}
                                            value={String(warehouse.id)}
                                        >
                                            {warehouse.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.warehouse_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="product_id">المادة *</Label>
                            <MaterialSearchSelect
                                materials={materialSearchOptions}
                                value={data.product_id}
                                onChange={(value) => setData('product_id', value)}
                                placeholder={
                                    data.warehouse_id
                                        ? 'ابحث عن مادة أو اختر من القائمة...'
                                        : 'اختر المستودع أولاً'
                                }
                            />
                            <InputError message={errors.product_id} />
                            {selectedMaterial && (
                                <p className="text-sm text-muted-foreground">
                                    المتاح:{' '}
                                    <span className="font-medium text-foreground">
                                        {formatQuantity(selectedMaterial.available_qty)}
                                        {selectedMaterial.unit?.abbreviation
                                            ? ` ${selectedMaterial.unit.abbreviation}`
                                            : ''}
                                    </span>
                                    {' · '}
                                    تكلفة الوحدة:{' '}
                                    {formatCurrency(selectedMaterial.cost_price)}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="quantity">الكمية *</Label>
                                <Input
                                    id="quantity"
                                    type="number"
                                    min="0.0001"
                                    step="any"
                                    value={String(data.quantity)}
                                    onChange={(e) =>
                                        setData('quantity', Number(e.target.value))
                                    }
                                    disabled={!data.product_id}
                                    className={cn(exceedsAvailable && 'border-destructive')}
                                />
                                <InputError message={errors.quantity} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reason">سبب الهدر *</Label>
                                <Select
                                    value={data.reason}
                                    onValueChange={(value) => setData('reason', value)}
                                    required
                                >
                                    <SelectTrigger id="reason" className="h-11 w-full">
                                        <SelectValue placeholder="اختر السبب" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {reasonOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.reason} />
                            </div>
                        </div>

                        {exceedsAvailable && (
                            <div className="flex items-start gap-2 rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                                <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                <span>
                                    الكمية المدخلة أكبر من المتاح في المستودع (
                                    {formatQuantity(selectedMaterial.available_qty)}).
                                </span>
                            </div>
                        )}

                        {selectedMaterial && Number(data.quantity) > 0 && (
                            <div className="rounded-lg bg-muted/50 px-4 py-3 text-sm">
                                <span className="text-muted-foreground">قيمة الهدر: </span>
                                <span className="font-semibold">{formatCurrency(lineValue)}</span>
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="notes">ملاحظات</Label>
                            <Input
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder="تفاصيل إضافية (اختياري)"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={
                                    processing ||
                                    !data.warehouse_id ||
                                    !data.product_id ||
                                    !data.reason ||
                                    exceedsAvailable ||
                                    Number(data.quantity) <= 0
                                }
                            >
                                {processing && <Spinner />}
                                تسجيل وخصم المخزون
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/wastage-records">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
