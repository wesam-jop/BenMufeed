import { Head, Link, useForm } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
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

type SupplierOption = { id: number; name: string };
type WarehouseOption = { id: number; name: string };

type Props = {
    suppliers: SupplierOption[];
    warehouses: WarehouseOption[];
};

export default function PurchaseInvoicesCreate({ suppliers, warehouses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        supplier_id: '',
        warehouse_id: '',
        invoice_number: '',
        due_date: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/purchase-invoices');
    };

    return (
        <>
            <Head title="فاتورة شراء جديدة" />
            <PageHeader
                title="فاتورة شراء جديدة"
                description="اختر المورد والمستودع — بعدها أضف المواد واستلمها للمخزون"
            />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Receipt className="size-5" />
                        إعداد الفاتورة
                    </CardTitle>
                    <CardDescription>
                        عند الاستلام تُضاف الكميات للمستودع ويُحدَّث سعر التكلفة
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="supplier_id">المورد *</Label>
                            <Select
                                value={data.supplier_id}
                                onValueChange={(value) => setData('supplier_id', value)}
                                required
                            >
                                <SelectTrigger id="supplier_id" className="h-11 w-full">
                                    <SelectValue placeholder="اختر المورد" />
                                </SelectTrigger>
                                <SelectContent>
                                    {suppliers.map((supplier) => (
                                        <SelectItem
                                            key={supplier.id}
                                            value={String(supplier.id)}
                                        >
                                            {supplier.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.supplier_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="warehouse_id">المستودع *</Label>
                            <Select
                                value={data.warehouse_id}
                                onValueChange={(value) => setData('warehouse_id', value)}
                                required
                            >
                                <SelectTrigger id="warehouse_id" className="h-11 w-full">
                                    <SelectValue placeholder="أين تُخزَّن المواد؟" />
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

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="invoice_number">
                                    رقم الفاتورة{' '}
                                    <span className="text-xs text-muted-foreground">(اختياري)</span>
                                </Label>
                                <Input
                                    id="invoice_number"
                                    value={data.invoice_number}
                                    onChange={(e) => setData('invoice_number', e.target.value)}
                                    placeholder="يُولَّد تلقائياً إن تُرك فارغاً"
                                />
                                <InputError message={errors.invoice_number} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="due_date">
                                    تاريخ الاستحقاق{' '}
                                    <span className="text-xs text-muted-foreground">(اختياري)</span>
                                </Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    value={data.due_date}
                                    onChange={(e) => setData('due_date', e.target.value)}
                                />
                                <InputError message={errors.due_date} />
                            </div>
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button
                                type="submit"
                                disabled={
                                    processing || !data.supplier_id || !data.warehouse_id
                                }
                            >
                                {processing && <Spinner />}
                                متابعة
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/purchase-invoices">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
