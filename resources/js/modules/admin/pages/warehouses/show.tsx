import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Package, Pencil, Warehouse as WarehouseIcon, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatCurrency,
    formatQuantity,
    warehouseTypeLabel,
} from '@/lib/format';

type MaterialRow = {
    id: number;
    name: string;
    sku: string | null;
    quantity: number;
    unit_cost: number;
    line_value: number;
    unit: { name: string; abbreviation: string } | null;
};

type WarehouseDetail = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    is_active: boolean;
    materials_count: number;
    total_value: number;
    materials: MaterialRow[];
    summary: {
        materials_count: number;
        total_quantity: number;
        total_value: number;
    };
};

type Props = {
    warehouse: WarehouseDetail;
};

export default function WarehousesShow({ warehouse }: Props) {
    return (
        <>
            <Head title={warehouse.name} />
            <PageHeader
                title={warehouse.name}
                description="رصيد المواد المخزّنة في هذا المستودع"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/warehouses">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/admin/warehouses/${warehouse.id}/edit`}>
                        <Pencil className="size-4" />
                        تعديل
                    </Link>
                </Button>
                <ResourceActions
                    deleteHref={`/admin/warehouses/${warehouse.id}`}
                    deletePermission="warehouses.delete"
                />
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant="outline">
                    {warehouse.type_label || warehouseTypeLabel(warehouse.type)}
                </Badge>
                <Badge variant={warehouse.is_active ? 'default' : 'secondary'}>
                    {warehouse.is_active ? 'نشط' : 'موقوف'}
                </Badge>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title="المواد المخزّنة"
                    value={warehouse.summary.materials_count}
                    format="number"
                    icon={Package}
                />
                <StatCard
                    title="إجمالي الكميات"
                    value={formatQuantity(warehouse.summary.total_quantity)}
                    icon={WarehouseIcon}
                />
                <StatCard
                    title="قيمة المخزون"
                    value={warehouse.summary.total_value}
                    format="currency"
                    icon={Wallet}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>المواد في المستودع</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {warehouse.materials.length === 0 ? (
                        <div className="px-6 py-12 text-center text-muted-foreground">
                            لا توجد مواد برصيد في هذا المستودع
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">المادة</th>
                                        <th className="px-4 py-3 font-medium">الكمية</th>
                                        <th className="px-4 py-3 font-medium">تكلفة الوحدة</th>
                                        <th className="px-4 py-3 font-medium">القيمة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {warehouse.materials.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="border-b hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{item.name}</div>
                                                {item.sku && (
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.sku}
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
                                                {formatCurrency(item.unit_cost)}
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
        </>
    );
}
