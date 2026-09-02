import { Check, RefreshCw, UtensilsCrossed, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import OrderStatusBadge from '@/components/domain/OrderStatusBadge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { acceptTableOrder, rejectTableOrder } from '@/lib/pos-api';
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

export type PendingTableOrder = {
    id: number;
    order_number: string;
    status: string;
    total: number;
    subtotal?: number;
    tax_amount?: number;
    notes?: string | null;
    created_at?: string | null;
    table_name?: string | null;
    items: Array<{
        id: number;
        name: string;
        quantity: number;
        unit_price: number;
    }>;
};

type Props = {
    orders: PendingTableOrder[];
    onRefresh: () => void;
};

export default function PosTableOrderQueue({ orders, onRefresh }: Props) {
    const { t } = useI18n();
    const [selectedId, setSelectedId] = useState<number | null>(orders[0]?.id ?? null);
    const [processing, setProcessing] = useState(false);
    const [rejectOpen, setRejectOpen] = useState(false);
    const [rejectReason, setRejectReason] = useState('');

    useEffect(() => {
        if (orders.length === 0) {
            setSelectedId(null);
            return;
        }

        if (!selectedId || !orders.some((o) => o.id === selectedId)) {
            setSelectedId(orders[0].id);
        }
    }, [orders, selectedId]);

    const selected = orders.find((o) => o.id === selectedId) ?? null;

    const handleAccept = async (orderId: number) => {
        try {
            setProcessing(true);
            const result = await acceptTableOrder(orderId);
            toast.success(result.message ?? 'تم قبول الطلب وإرساله للمطبخ');
            onRefresh();
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'تعذر قبول الطلب');
        } finally {
            setProcessing(false);
        }
    };

    const handleReject = async () => {
        if (!selected) {
            return;
        }

        try {
            setProcessing(true);
            const result = await rejectTableOrder(selected.id, rejectReason || undefined);
            toast.success(result.message ?? 'تم رفض الطلب');
            setRejectOpen(false);
            setRejectReason('');
            onRefresh();
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'تعذر رفض الطلب');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="flex h-full min-h-0 flex-col gap-4 lg:flex-row">
            <div className="flex w-full shrink-0 flex-col rounded-xl border bg-card lg:w-80">
                <div className="flex items-center justify-between border-b px-4 py-3">
                    <div className="flex items-center gap-2">
                        <UtensilsCrossed className="size-4 text-amber-700" />
                        <h2 className="font-semibold">طلبات الطاولات</h2>
                        {orders.length > 0 && (
                            <Badge className="bg-amber-800">{orders.length}</Badge>
                        )}
                    </div>
                    <Button variant="ghost" size="icon" onClick={onRefresh} aria-label="تحديث">
                        <RefreshCw className="size-4" />
                    </Button>
                </div>
                <div className="min-h-0 flex-1 overflow-y-auto p-2">
                    {orders.length === 0 ? (
                        <p className="p-6 text-center text-sm text-muted-foreground">
                            لا توجد طلبات طاولات بانتظار الموافقة
                        </p>
                    ) : (
                        <div className="grid gap-2">
                            {orders.map((order) => (
                                <button
                                    key={order.id}
                                    type="button"
                                    onClick={() => setSelectedId(order.id)}
                                    className={cn(
                                        'rounded-lg border p-3 text-start transition-colors',
                                        selectedId === order.id
                                            ? 'border-amber-300 bg-amber-50'
                                            : 'hover:bg-muted/50',
                                    )}
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-semibold">{order.order_number}</span>
                                        <OrderStatusBadge status={order.status} />
                                    </div>
                                    <p className="mt-1 text-sm text-amber-900">
                                        {order.table_name ?? 'طاولة'}
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {formatCurrency(order.total)}
                                        {order.created_at && (
                                            <span className="ms-2">{formatDate(order.created_at)}</span>
                                        )}
                                    </p>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="min-h-0 flex-1 rounded-xl border bg-card">
                {!selected ? (
                    <div className="flex h-full items-center justify-center p-8 text-muted-foreground">
                        اختر طلباً لعرض التفاصيل
                    </div>
                ) : (
                    <div className="flex h-full flex-col">
                        <div className="border-b px-5 py-4">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 className="text-xl font-bold">{selected.order_number}</h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        طاولة: {selected.table_name ?? '—'}
                                    </p>
                                </div>
                                <OrderStatusBadge status={selected.status} />
                            </div>
                        </div>
                        <div className="min-h-0 flex-1 overflow-y-auto p-5">
                            <div className="grid gap-3">
                                {selected.items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between rounded-lg border px-4 py-3"
                                    >
                                        <div>
                                            <p className="font-medium">{item.name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                الكمية: {item.quantity}
                                            </p>
                                        </div>
                                        <p className="font-semibold">
                                            {formatCurrency(item.unit_price * item.quantity)}
                                        </p>
                                    </div>
                                ))}
                            </div>
                            {selected.notes && (
                                <p className="mt-4 rounded-lg bg-muted/50 p-3 text-sm">
                                    ملاحظات: {selected.notes}
                                </p>
                            )}
                            <p className="mt-4 text-lg font-bold">
                                الإجمالي: {formatCurrency(selected.total)}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2 border-t p-4">
                            <Button
                                className="bg-emerald-700 hover:bg-emerald-800"
                                disabled={processing}
                                onClick={() => handleAccept(selected.id)}
                            >
                                {processing ? <Spinner /> : <Check className="size-4" />}
                                موافقة وإرسال للمطبخ
                            </Button>
                            <Button
                                variant="destructive"
                                disabled={processing}
                                onClick={() => setRejectOpen(true)}
                            >
                                <X className="size-4" />
                                رفض
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>رفض طلب الطاولة</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="reject_reason">سبب الرفض (اختياري)</Label>
                        <Input
                            id="reject_reason"
                            value={rejectReason}
                            onChange={(e) => setRejectReason(e.target.value)}
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRejectOpen(false)}>
                            إلغاء
                        </Button>
                        <Button variant="destructive" disabled={processing} onClick={handleReject}>
                            تأكيد الرفض
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
