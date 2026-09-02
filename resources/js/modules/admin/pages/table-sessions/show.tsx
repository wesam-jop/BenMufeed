import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatCurrency,
    formatDate,
    orderStatusLabel,
    orderStatusVariant,
    statusLabel,
} from '@/lib/format';

type OrderRow = {
    id: number;
    order_number?: string | null;
    status: string;
    total: number;
    items_count?: number;
    created_at?: string | null;
};

type TableSession = {
    id: number;
    status: string;
    guest_count?: number | null;
    opened_at?: string | null;
    closed_at?: string | null;
    table?: {
        name: string;
        zone?: string | null;
        capacity?: number | null;
        status?: string | null;
    } | null;
    orders: OrderRow[];
};

type Props = {
    tableSession: TableSession;
};

export default function TableSessionsShow({ tableSession }: Props) {

    return (
        <>
            <Head title={`جلسة ${tableSession.table?.name ?? tableSession.id}`} />
            <PageHeader
                title={`جلسة ${tableSession.table?.name ?? `#${tableSession.id}`}`}
                description="تفاصيل جلسة الطاولة والطلبات المرتبطة"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/table-sessions">
                            <ArrowRight className="size-4" />
                            رجوع
                        </Link>
                    </Button>
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>طلبات الجلسة</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {tableSession.orders.length === 0 ? (
                            <p className="text-sm text-muted-foreground">لا توجد طلبات لهذه الجلسة</p>
                        ) : (
                            tableSession.orders.map((order) => (
                                <Link
                                    key={order.id}
                                    href={`/admin/orders/${order.id}`}
                                    className="flex items-center justify-between rounded-lg border px-3 py-2 transition-colors hover:bg-muted/40"
                                >
                                    <div>
                                        <p className="font-medium">#{order.order_number ?? order.id}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {order.items_count ?? 0} صنف
                                            {order.created_at ? ` · ${formatDate(order.created_at)}` : ''}
                                        </p>
                                    </div>
                                    <div className="text-left">
                                        <p className="font-semibold text-emerald-600">
                                            {formatCurrency(order.total)}
                                        </p>
                                        <Badge variant={orderStatusVariant(order.status)} className="mt-1">
                                            {orderStatusLabel(order.status)}
                                        </Badge>
                                    </div>
                                </Link>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>معلومات الجلسة</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">الحالة</span>
                            <Badge>{statusLabel(tableSession.status)}</Badge>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">عدد الضيوف</span>
                            <span>{tableSession.guest_count ?? '—'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">فتحت</span>
                            <span>
                                {tableSession.opened_at ? formatDate(tableSession.opened_at) : '—'}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">أُغلقت</span>
                            <span>
                                {tableSession.closed_at ? formatDate(tableSession.closed_at) : '—'}
                            </span>
                        </div>
                        {tableSession.table && (
                            <>
                                <hr />
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">الطاولة</span>
                                    <span>{tableSession.table.name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">المنطقة</span>
                                    <span>{tableSession.table.zone ?? '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">السعة</span>
                                    <span>{tableSession.table.capacity ?? '—'}</span>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
