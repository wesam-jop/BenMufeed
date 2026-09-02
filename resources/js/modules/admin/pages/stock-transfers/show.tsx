import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeftRight, CheckCircle2, Plus, Save, Trash2, Truck } from 'lucide-react';
import MaterialSearchSelect, {
    type MaterialSearchOption,
} from '@/components/admin/material-search-select';
import PageHeader from '@/components/admin/page-header';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    formatCurrency,
    formatDate,
    formatQuantity,
    stockTransferStatusLabel,
    stockTransferStatusVariant,
} from '@/lib/format';
import { cn } from '@/lib/utils';

type TransferItem = {
    id: number;
    product_id: number;
    product_name: string;
    product_sku: string | null;
    unit: { name: string; abbreviation: string } | null;
    quantity: number;
    available_qty: number;
    line_value: number;
};

type TransferDetail = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    from_warehouse_name: string;
    to_warehouse_name: string;
    user_name: string | null;
    approver_name: string | null;
    created_at: string | null;
    can_edit: boolean;
    items: TransferItem[];
    summary: {
        items_count: number;
        total_quantity: number;
        total_value: number;
    };
};

type AvailableMaterial = MaterialSearchOption & {
    available_qty: number;
};

type TransferItemForm = {
    id?: number;
    product_id: string;
    quantity: number;
};

type Props = {
    stockTransfer: TransferDetail;
    availableMaterials: AvailableMaterial[];
};

export default function StockTransfersShow({ stockTransfer, availableMaterials }: Props) {
    const { data, setData, put, processing, errors, transform } = useForm<{ items: TransferItemForm[] }>({
        items:
            stockTransfer.items.length > 0
                ? stockTransfer.items.map((item) => ({
                      id: item.id,
                      product_id: String(item.product_id),
                      quantity: item.quantity,
                  }))
                : [{ product_id: '', quantity: 1 }],
    });

    transform((formData) => ({
        items: formData.items
            .filter((row) => row.product_id)
            .map((row) => ({
                id: row.id,
                product_id: Number(row.product_id),
                quantity: Number(row.quantity),
            })),
    }));

    const materialOptions: MaterialSearchOption[] = availableMaterials.map((material) => ({
        id: material.id,
        name: material.name,
        cost_price: material.cost_price,
        unit: material.unit,
    }));

    const liveSummary = data.items.reduce(
        (acc, row) => {
            if (!row.product_id) {
                return acc;
            }

            const material = availableMaterials.find(
                (item) => item.id === Number(row.product_id),
            );
            const quantity = Number(row.quantity) || 0;

            return {
                count: acc.count + 1,
                quantity: acc.quantity + quantity,
                value: acc.value + quantity * (material?.cost_price ?? 0),
            };
        },
        { count: 0, quantity: 0, value: 0 },
    );

    const saveTransfer = (e: React.FormEvent) => {
        e.preventDefault();

        put(`/admin/stock-transfers/${stockTransfer.id}`);
    };

    const executeTransfer = () => {
        if (
            !confirm(
                'تنفيذ التحويل سيخصم الكميات من المستودع المصدر ويضيفها للوجهة. هل تريد المتابعة؟',
            )
        ) {
            return;
        }

        router.post(`/admin/stock-transfers/${stockTransfer.id}/execute`);
    };

    const addRow = () => {
        setData('items', [...data.items, { product_id: '', quantity: 1 }]);
    };

    const removeRow = (index: number) => {
        setData(
            'items',
            data.items.filter((_, rowIndex) => rowIndex !== index),
        );
    };

    const updateRow = (index: number, field: keyof TransferItemForm, value: string | number) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: value };
        setData('items', next);
    };

    return (
        <>
            <Head title={`تحويل ${stockTransfer.reference}`} />
            <PageHeader
                title={`تحويل ${stockTransfer.reference}`}
                description={`${stockTransfer.from_warehouse_name} → ${stockTransfer.to_warehouse_name}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/stock-transfers">رجوع للقائمة</Link>
                    </Button>
                }
            />

            <div className="mb-6 flex flex-wrap items-center gap-2">
                <Badge variant={stockTransferStatusVariant(stockTransfer.status)}>
                    {stockTransferStatusLabel(stockTransfer.status)}
                </Badge>
                <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                    <ArrowLeftRight className="size-4" />
                    {stockTransfer.from_warehouse_name} → {stockTransfer.to_warehouse_name}
                </span>
                {stockTransfer.created_at && (
                    <span className="text-sm text-muted-foreground">
                        {formatDate(stockTransfer.created_at)}
                    </span>
                )}
                {stockTransfer.approver_name && (
                    <span className="text-sm text-muted-foreground">
                        نفّذه: {stockTransfer.approver_name}
                    </span>
                )}
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title="عدد المواد"
                    value={stockTransfer.can_edit ? liveSummary.count : stockTransfer.summary.items_count}
                    format="number"
                    icon={Truck}
                />
                <StatCard
                    title="إجمالي الكمية"
                    value={stockTransfer.can_edit ? liveSummary.quantity : stockTransfer.summary.total_quantity}
                    format="number"
                />
                <StatCard
                    title="قيمة التحويل"
                    value={stockTransfer.can_edit ? liveSummary.value : stockTransfer.summary.total_value}
                    format="currency"
                />
            </div>

            <form onSubmit={saveTransfer}>
                <Card>
                    <CardContent className="grid gap-4 p-4">
                        {stockTransfer.can_edit ? (
                            <>
                                {data.items.map((row, index) => {
                                    const material = availableMaterials.find(
                                        (item) => item.id === Number(row.product_id),
                                    );
                                    const exceedsAvailable =
                                        material && Number(row.quantity) > material.available_qty;

                                    return (
                                        <div
                                            key={index}
                                            className="grid gap-4 rounded-lg border p-4"
                                        >
                                            <div className="grid gap-2">
                                                <label className="text-sm font-medium">المادة</label>
                                                <MaterialSearchSelect
                                                    materials={materialOptions}
                                                    value={row.product_id}
                                                    onChange={(value) =>
                                                        updateRow(index, 'product_id', value)
                                                    }
                                                    placeholder="ابحث عن مادة للتحويل..."
                                                />
                                            </div>

                                            <div className="grid gap-3 sm:grid-cols-[160px_1fr_auto] sm:items-end">
                                                <div className="grid gap-2">
                                                    <label className="text-sm font-medium">
                                                        الكمية
                                                    </label>
                                                    <Input
                                                        type="number"
                                                        min="0.0001"
                                                        step="0.01"
                                                        value={String(row.quantity)}
                                                        onChange={(e) =>
                                                            updateRow(
                                                                index,
                                                                'quantity',
                                                                Number(e.target.value),
                                                            )
                                                        }
                                                        className={cn(
                                                            'h-11',
                                                            exceedsAvailable &&
                                                                'border-destructive',
                                                        )}
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <label className="text-sm font-medium">
                                                        المتاح بالمصدر
                                                    </label>
                                                    <div className="flex h-11 items-center rounded-md border bg-muted/40 px-3 text-sm">
                                                        {material
                                                            ? `${formatQuantity(material.available_qty)}${material.unit ? ` ${material.unit.abbreviation}` : ''}`
                                                            : '—'}
                                                    </div>
                                                </div>

                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-11"
                                                    onClick={() => removeRow(index)}
                                                    disabled={data.items.length === 1}
                                                    aria-label="حذف السطر"
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>

                                            {exceedsAvailable && (
                                                <p className="text-sm text-destructive">
                                                    الكمية أكبر من المتاح في المستودع المصدر
                                                </p>
                                            )}
                                        </div>
                                    );
                                })}

                                <Button type="button" variant="outline" onClick={addRow}>
                                    <Plus className="size-4" />
                                    إضافة مادة
                                </Button>

                                {errors.items && (
                                    <p className="text-sm text-destructive">{errors.items}</p>
                                )}
                            </>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50 text-right">
                                            <th className="px-4 py-3 font-medium">المادة</th>
                                            <th className="px-4 py-3 font-medium">الكمية</th>
                                            <th className="px-4 py-3 font-medium">القيمة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {stockTransfer.items.map((item) => (
                                            <tr key={item.id} className="border-b">
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">
                                                        {item.product_name}
                                                    </div>
                                                    {item.product_sku && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.product_sku}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {formatQuantity(item.quantity)}
                                                    {item.unit ? ` ${item.unit.abbreviation}` : ''}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {formatCurrency(item.line_value)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {stockTransfer.can_edit && (
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="submit" variant="outline" disabled={processing}>
                            <Save className="size-4" />
                            حفظ المواد
                        </Button>
                        <Button type="button" onClick={executeTransfer}>
                            <CheckCircle2 className="size-4" />
                            تنفيذ التحويل
                        </Button>
                    </div>
                )}
            </form>
        </>
    );
}
