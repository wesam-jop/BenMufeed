import { Head, Link } from '@inertiajs/react';
import { ArrowRight, FileWarning } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatCurrency,
    formatDate,
    formatQuantity,
    wastageReasonLabel,
} from '@/lib/format';

type WastageDetail = {
    id: number;
    reference: string;
    warehouse_name: string;
    product_name: string;
    product_sku: string | null;
    unit: { name: string; abbreviation: string } | null;
    quantity: number;
    reason: string;
    reason_label: string;
    line_value: number;
    unit_cost: number;
    user_name: string | null;
    notes: string | null;
    stock_movement_id: number | null;
    created_at: string | null;
};

type Props = {
    wastageRecord: WastageDetail;
};

export default function WastageRecordsShow({ wastageRecord }: Props) {
    return (
        <>
            <Head title={`سجل هدر ${wastageRecord.reference}`} />
            <PageHeader
                title={wastageRecord.reference}
                description="تفاصيل سجل الهدر — تم خصم المخزون تلقائياً"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/wastage-records">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <ResourceActions
                    deleteHref={`/admin/wastage-records/${wastageRecord.id}`}
                    deletePermission="wastage.delete"
                />
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title="الكمية المهدرة"
                    value={`${formatQuantity(wastageRecord.quantity)}${wastageRecord.unit?.abbreviation ? ` ${wastageRecord.unit.abbreviation}` : ''}`}
                    icon={FileWarning}
                />
                <StatCard
                    title="قيمة الهدر"
                    value={wastageRecord.line_value}
                    format="currency"
                />
                <StatCard
                    title="تكلفة الوحدة"
                    value={wastageRecord.unit_cost}
                    format="currency"
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>التفاصيل</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <DetailRow label="المستودع" value={wastageRecord.warehouse_name} />
                    <DetailRow
                        label="المادة"
                        value={
                            wastageRecord.product_sku
                                ? `${wastageRecord.product_name} (${wastageRecord.product_sku})`
                                : wastageRecord.product_name
                        }
                    />
                    <DetailRow
                        label="السبب"
                        value={
                            <Badge variant="destructive">
                                {wastageRecord.reason_label ||
                                    wastageReasonLabel(wastageRecord.reason)}
                            </Badge>
                        }
                    />
                    <DetailRow label="بواسطة" value={wastageRecord.user_name ?? '—'} />
                    <DetailRow
                        label="التاريخ"
                        value={
                            wastageRecord.created_at
                                ? formatDate(wastageRecord.created_at)
                                : '—'
                        }
                    />
                    <DetailRow
                        label="حركة المخزون"
                        value={
                            wastageRecord.stock_movement_id
                                ? `#${wastageRecord.stock_movement_id}`
                                : '—'
                        }
                    />
                    {wastageRecord.notes && (
                        <div className="sm:col-span-2">
                            <DetailRow label="ملاحظات" value={wastageRecord.notes} />
                        </div>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

function DetailRow({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}) {
    return (
        <div className="space-y-1">
            <p className="text-sm text-muted-foreground">{label}</p>
            <div className="font-medium">{value}</div>
        </div>
    );
}
