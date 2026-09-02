import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Plus, Receipt, Save, Trash2 } from 'lucide-react';
import MaterialSearchSelect, {
    type MaterialSearchOption,
} from '@/components/admin/material-search-select';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatCurrency,
    formatDate,
    formatQuantity,
    purchaseInvoiceStatusLabel,
    purchaseInvoiceStatusVariant,
} from '@/lib/format';

type InvoiceItem = {
    id: number;
    product_id: number;
    product_name: string;
    product_sku: string | null;
    unit: { name: string; abbreviation: string } | null;
    quantity: number;
    unit_price: number;
    line_value: number;
};

type InvoiceDetail = {
    id: number;
    invoice_number: string;
    supplier_name: string;
    warehouse_name: string;
    status: string;
    status_label: string;
    subtotal: number;
    tax: number;
    total: number;
    paid: number;
    balance: number;
    due_date: string | null;
    user_name: string | null;
    created_at: string | null;
    can_edit: boolean;
    items: InvoiceItem[];
    summary: {
        items_count: number;
        total_quantity: number;
        subtotal: number;
    };
};

type AvailableMaterial = MaterialSearchOption & {
    sku: string | null;
};

type InvoiceItemForm = {
    id?: number;
    product_id: string;
    quantity: number;
    unit_price: number;
};

type Props = {
    purchaseInvoice: InvoiceDetail;
    availableMaterials: AvailableMaterial[];
};

export default function PurchaseInvoicesShow({
    purchaseInvoice,
    availableMaterials,
}: Props) {
    const { data, setData, put, processing, errors, transform } = useForm<{
        items: InvoiceItemForm[];
        tax: number;
    }>({
        items:
            purchaseInvoice.items.length > 0
                ? purchaseInvoice.items.map((item) => ({
                      id: item.id,
                      product_id: String(item.product_id),
                      quantity: item.quantity,
                      unit_price: item.unit_price,
                  }))
                : [{ product_id: '', quantity: 1, unit_price: 0 }],
        tax: purchaseInvoice.tax,
    });

    transform((formData) => ({
        items: formData.items
            .filter((row) => row.product_id)
            .map((row) => ({
                id: row.id,
                product_id: Number(row.product_id),
                quantity: Number(row.quantity),
                unit_price: Number(row.unit_price),
            })),
        tax: Number(formData.tax) || 0,
    }));

    const materialOptions: MaterialSearchOption[] = availableMaterials.map((material) => ({
        id: material.id,
        name: material.name,
        cost_price: material.cost_price,
        unit: material.unit,
    }));

    const liveSubtotal = data.items.reduce((sum, row) => {
        if (!row.product_id) {
            return sum;
        }

        return sum + (Number(row.quantity) || 0) * (Number(row.unit_price) || 0);
    }, 0);

    const liveTotal = liveSubtotal + (Number(data.tax) || 0);

    const saveInvoice = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/purchase-invoices/${purchaseInvoice.id}`);
    };

    const receivePurchase = () => {
        if (
            !confirm(
                'استلام الشراء سيُضيف المواد للمستودع ويُحدّث تكلفة المواد. هل تريد المتابعة؟',
            )
        ) {
            return;
        }

        router.post(`/admin/purchase-invoices/${purchaseInvoice.id}/receive`);
    };

    const addRow = () => {
        setData('items', [...data.items, { product_id: '', quantity: 1, unit_price: 0 }]);
    };

    const removeRow = (index: number) => {
        setData(
            'items',
            data.items.filter((_, rowIndex) => rowIndex !== index),
        );
    };

    const updateRow = (
        index: number,
        field: keyof InvoiceItemForm,
        value: string | number,
    ) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: value };

        if (field === 'product_id') {
            const material = availableMaterials.find((item) => item.id === Number(value));
            if (material) {
                next[index].unit_price = material.cost_price;
            }
        }

        setData('items', next);
    };

    return (
        <>
            <Head title={`فاتورة ${purchaseInvoice.invoice_number}`} />
            <PageHeader
                title={purchaseInvoice.invoice_number}
                description="شراء مواد وإضافتها للمخزون"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/purchase-invoices">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                {purchaseInvoice.can_edit && (
                    <ResourceActions
                        deleteHref={`/admin/purchase-invoices/${purchaseInvoice.id}`}
                        deletePermission="purchase-invoices.delete"
                    />
                )}
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant={purchaseInvoiceStatusVariant(purchaseInvoice.status)}>
                    {purchaseInvoice.status_label ||
                        purchaseInvoiceStatusLabel(purchaseInvoice.status)}
                </Badge>
            </div>

            <div className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatCard title="المورد" value={purchaseInvoice.supplier_name} icon={Receipt} />
                <StatCard title="المستودع" value={purchaseInvoice.warehouse_name} />
                <StatCard
                    title="المواد"
                    value={purchaseInvoice.summary.items_count}
                    format="number"
                />
                <StatCard title="الإجمالي" value={purchaseInvoice.total} format="currency" />
            </div>

            {purchaseInvoice.can_edit ? (
                <Card>
                    <CardHeader>
                        <CardTitle>مواد الشراء</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={saveInvoice} className="space-y-4">
                            <div className="space-y-3">
                                {data.items.map((row, index) => (
                                    <div
                                        key={index}
                                        className="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_120px_120px_40px]"
                                    >
                                        <div className="grid gap-2">
                                            <Label>المادة</Label>
                                            <MaterialSearchSelect
                                                materials={materialOptions}
                                                value={row.product_id}
                                                onChange={(value) =>
                                                    updateRow(index, 'product_id', value)
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>الكمية</Label>
                                            <Input
                                                type="number"
                                                min="0.0001"
                                                step="any"
                                                value={String(row.quantity)}
                                                onChange={(e) =>
                                                    updateRow(
                                                        index,
                                                        'quantity',
                                                        Number(e.target.value),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>سعر الوحدة</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                step="any"
                                                value={String(row.unit_price)}
                                                onChange={(e) =>
                                                    updateRow(
                                                        index,
                                                        'unit_price',
                                                        Number(e.target.value),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="flex items-end">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => removeRow(index)}
                                                disabled={data.items.length === 1}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <Button type="button" variant="outline" size="sm" onClick={addRow}>
                                <Plus className="size-4" />
                                إضافة مادة
                            </Button>

                            <div className="grid max-w-xs gap-2">
                                <Label htmlFor="tax">الضريبة (اختياري)</Label>
                                <Input
                                    id="tax"
                                    type="number"
                                    min="0"
                                    step="any"
                                    value={String(data.tax)}
                                    onChange={(e) => setData('tax', Number(e.target.value))}
                                />
                            </div>

                            <div className="rounded-lg bg-muted/50 px-4 py-3 text-sm">
                                <div>
                                    <span className="text-muted-foreground">المجموع: </span>
                                    <span className="font-semibold">
                                        {formatCurrency(liveSubtotal)}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">الإجمالي: </span>
                                    <span className="font-semibold">
                                        {formatCurrency(liveTotal)}
                                    </span>
                                </div>
                            </div>

                            {errors.items && (
                                <p className="text-sm text-destructive">{errors.items}</p>
                            )}

                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button type="submit" disabled={processing}>
                                    {processing ? null : <Save className="size-4" />}
                                    حفظ المواد
                                </Button>
                                <Button
                                    type="button"
                                    variant="default"
                                    onClick={receivePurchase}
                                    disabled={
                                        processing ||
                                        data.items.every((row) => !row.product_id)
                                    }
                                >
                                    <CheckCircle2 className="size-4" />
                                    استلام وإضافة للمخزون
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardHeader>
                        <CardTitle>مواد الشراء</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">المادة</th>
                                        <th className="px-4 py-3 font-medium">الكمية</th>
                                        <th className="px-4 py-3 font-medium">سعر الوحدة</th>
                                        <th className="px-4 py-3 font-medium">القيمة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {purchaseInvoice.items.map((item) => (
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
                                                {item.unit?.abbreviation
                                                    ? ` ${item.unit.abbreviation}`
                                                    : ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.unit_price)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatCurrency(item.line_value)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="space-y-1 px-4 py-4 text-sm">
                            <div>
                                <span className="text-muted-foreground">المجموع: </span>
                                {formatCurrency(purchaseInvoice.subtotal)}
                            </div>
                            <div>
                                <span className="text-muted-foreground">الضريبة: </span>
                                {formatCurrency(purchaseInvoice.tax)}
                            </div>
                            <div className="font-semibold">
                                الإجمالي: {formatCurrency(purchaseInvoice.total)}
                            </div>
                            {purchaseInvoice.created_at && (
                                <div className="text-muted-foreground">
                                    {formatDate(purchaseInvoice.created_at)}
                                    {purchaseInvoice.user_name
                                        ? ` · ${purchaseInvoice.user_name}`
                                        : ''}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
