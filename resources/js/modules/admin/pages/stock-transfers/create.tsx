import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type WarehouseOption = { id: number; name: string };

type Props = {
    warehouses: WarehouseOption[];
};

export default function StockTransfersCreate({ warehouses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        from_warehouse_id: '',
        to_warehouse_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/stock-transfers');
    };

    return (
        <>
            <Head title="تحويل جديد" />
            <PageHeader
                title="تحويل مخزون جديد"
                description="اختر المستودع المصدر والوجهة ثم أضف المواد"
            />
            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle>إعداد التحويل</CardTitle>
                    <CardDescription>
                        سيتم خصم الكميات من المستودع المصدر وإضافتها للوجهة عند التنفيذ
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="from_warehouse_id">من مستودع *</Label>
                            <Select
                                value={data.from_warehouse_id}
                                onValueChange={(value) => setData('from_warehouse_id', value)}
                                required
                            >
                                <SelectTrigger id="from_warehouse_id" className="h-11 w-full">
                                    <SelectValue placeholder="المستودع المصدر" />
                                </SelectTrigger>
                                <SelectContent>
                                    {warehouses.map((warehouse) => (
                                        <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                            {warehouse.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.from_warehouse_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="to_warehouse_id">إلى مستودع *</Label>
                            <Select
                                value={data.to_warehouse_id}
                                onValueChange={(value) => setData('to_warehouse_id', value)}
                                required
                            >
                                <SelectTrigger id="to_warehouse_id" className="h-11 w-full">
                                    <SelectValue placeholder="المستودع الوجهة" />
                                </SelectTrigger>
                                <SelectContent>
                                    {warehouses
                                        .filter(
                                            (warehouse) =>
                                                String(warehouse.id) !== data.from_warehouse_id,
                                        )
                                        .map((warehouse) => (
                                            <SelectItem
                                                key={warehouse.id}
                                                value={String(warehouse.id)}
                                            >
                                                {warehouse.name}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.to_warehouse_id} />
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    !data.from_warehouse_id ||
                                    !data.to_warehouse_id ||
                                    data.from_warehouse_id === data.to_warehouse_id
                                }
                            >
                                {processing && <Spinner />}
                                متابعة
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/stock-transfers">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
