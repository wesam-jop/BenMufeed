import { Head, Link, router, useForm } from '@inertiajs/react';
import { CheckCircle2, ClipboardList, Save } from 'lucide-react';
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
    stockCountStatusLabel,
    stockCountStatusVariant,
} from '@/lib/format';
import { cn } from '@/lib/utils';

type CountItem = {
    id: number;
    product_name: string;
    product_sku: string | null;
    unit: { name: string; abbreviation: string } | null;
    cost_price: number;
    system_qty: number;
    actual_qty: number;
    difference: number;
    variance_value: number;
};

type StockCountDetail = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    warehouse_name: string;
    approver_name: string | null;
    counted_at: string | null;
    created_at: string | null;
    can_edit: boolean;
    items: CountItem[];
    summary: {
        items_count: number;
        differences_count: number;
        matched_count: number;
        total_variance_value: number;
    };
};

type Props = {
    stockCount: StockCountDetail;
};

export default function StockCountsShow({ stockCount }: Props) {
    const { data, setData, put, processing } = useForm({
        items: stockCount.items.map((item) => ({
            id: item.id,
            actual_qty: item.actual_qty,
        })),
    });

    const liveItems = stockCount.items.map((item, index) => {
        const actualQty = Number(data.items[index]?.actual_qty ?? item.actual_qty);
        const difference = actualQty - item.system_qty;
        const varianceValue = difference * item.cost_price;

        return {
            ...item,
            actual_qty: actualQty,
            difference,
            variance_value: varianceValue,
        };
    });

    const liveSummary = {
        differences_count: liveItems.filter((item) => item.difference !== 0).length,
        matched_count: liveItems.filter((item) => item.difference === 0).length,
        total_variance_value: liveItems.reduce((sum, item) => sum + item.variance_value, 0),
    };

    const saveCounts = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/stock-counts/${stockCount.id}`);
    };

    const approveCount = () => {
        if (
            !confirm(
                'اعتماد الجرد سيُصحّح المخزون تلقائياً حسب الفروقات. هل تريد المتابعة؟',
            )
        ) {
            return;
        }

        router.post(`/admin/stock-counts/${stockCount.id}/approve`);
    };

    const updateActualQty = (index: number, value: number) => {
        const next = [...data.items];
        next[index] = { ...next[index], actual_qty: value };
        setData('items', next);
    };

    return (
        <>
            <Head title={`جرد ${stockCount.reference}`} />
            <PageHeader
                title={`جرد ${stockCount.reference}`}
                description={`${stockCount.warehouse_name} — ${stockCount.status_label}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/stock-counts">رجوع للقائمة</Link>
                    </Button>
                }
            />

            <div className="mb-6 flex flex-wrap items-center gap-2">
                <Badge variant={stockCountStatusVariant(stockCount.status)}>
                    {stockCountStatusLabel(stockCount.status)}
                </Badge>
                {stockCount.counted_at && (
                    <span className="text-sm text-muted-foreground">
                        تاريخ الاعتماد: {formatDate(stockCount.counted_at)}
                    </span>
                )}
                {stockCount.approver_name && (
                    <span className="text-sm text-muted-foreground">
                        بواسطة: {stockCount.approver_name}
                    </span>
                )}
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title="عدد المواد"
                    value={stockCount.summary.items_count}
                    format="number"
                    icon={ClipboardList}
                />
                <StatCard
                    title="متطابق"
                    value={liveSummary.matched_count}
                    format="number"
                    description="بدون فرق"
                />
                <StatCard
                    title="فروقات"
                    value={liveSummary.differences_count}
                    format="number"
                    description="تحتاج تصحيح"
                />
                <StatCard
                    title="قيمة الفروقات"
                    value={Math.abs(liveSummary.total_variance_value)}
                    format="currency"
                    description={
                        liveSummary.total_variance_value >= 0 ? 'زيادة بالقيمة' : 'نقص بالقيمة'
                    }
                />
            </div>

            <form onSubmit={saveCounts}>
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">المادة</th>
                                        <th className="px-4 py-3 font-medium">رصيد النظام</th>
                                        <th className="px-4 py-3 font-medium">العدّ الفعلي</th>
                                        <th className="px-4 py-3 font-medium">الفرق</th>
                                        <th className="px-4 py-3 font-medium">قيمة الفرق</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {liveItems.map((item, index) => (
                                        <tr
                                            key={item.id}
                                            className={cn(
                                                'border-b',
                                                item.difference !== 0 && 'bg-amber-50/50',
                                                item.difference < 0 && 'bg-red-50/40',
                                                item.difference > 0 && 'bg-emerald-50/40',
                                            )}
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{item.product_name}</div>
                                                {item.product_sku && (
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.product_sku}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatQuantity(item.system_qty)}
                                                {item.unit ? ` ${item.unit.abbreviation}` : ''}
                                            </td>
                                            <td className="px-4 py-3">
                                                {stockCount.can_edit ? (
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={String(data.items[index]?.actual_qty ?? '')}
                                                        onChange={(e) =>
                                                            updateActualQty(
                                                                index,
                                                                Number(e.target.value),
                                                            )
                                                        }
                                                        className="h-10 w-32"
                                                    />
                                                ) : (
                                                    <>
                                                        {formatQuantity(item.actual_qty)}
                                                        {item.unit
                                                            ? ` ${item.unit.abbreviation}`
                                                            : ''}
                                                    </>
                                                )}
                                            </td>
                                            <td
                                                className={cn(
                                                    'px-4 py-3 font-semibold',
                                                    item.difference > 0 && 'text-emerald-700',
                                                    item.difference < 0 && 'text-red-700',
                                                )}
                                            >
                                                {item.difference > 0 ? '+' : ''}
                                                {formatQuantity(item.difference)}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatCurrency(item.variance_value)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {stockCount.can_edit && (
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button type="submit" variant="outline" disabled={processing}>
                            <Save className="size-4" />
                            حفظ الكميات
                        </Button>
                        <Button type="button" onClick={approveCount}>
                            <CheckCircle2 className="size-4" />
                            اعتماد الجرد وتصحيح المخزون
                        </Button>
                    </div>
                )}
            </form>
        </>
    );
}
