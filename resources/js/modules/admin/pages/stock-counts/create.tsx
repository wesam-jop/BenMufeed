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

export default function StockCountsCreate({ warehouses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        warehouse_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/stock-counts');
    };

    return (
        <>
            <Head title="جرد جديد" />
            <PageHeader
                title="بدء جرد جديد"
                description="اختر المستودع وسيتم تحميل كل المواد مع رصيدها الحالي"
            />
            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle>إعداد الجرد</CardTitle>
                    <CardDescription>
                        بعد البدء ستدخل الكميات الفعلية لكل مادة وتقارنها بما يسجّله النظام
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="warehouse_id">المستودع *</Label>
                            <Select
                                value={data.warehouse_id}
                                onValueChange={(value) => setData('warehouse_id', value)}
                                required
                            >
                                <SelectTrigger id="warehouse_id" className="h-11 w-full">
                                    <SelectValue placeholder="اختر المستودع" />
                                </SelectTrigger>
                                <SelectContent>
                                    {warehouses.map((warehouse) => (
                                        <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                            {warehouse.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.warehouse_id} />
                        </div>

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing || !data.warehouse_id}>
                                {processing && <Spinner />}
                                بدء الجرد
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/stock-counts">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
