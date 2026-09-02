import { Check, Inbox, MapPin, Phone, RefreshCw, X } from 'lucide-react';
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
import { acceptDeliveryOrder, rejectDeliveryOrder } from '@/lib/pos-api';
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

export type PendingDeliveryOrder = {
    id: number;
    order_number: string;
    status: string;
    total: number;
    subtotal?: number;
    tax_amount?: number;
    delivery_fee?: number;
    delivery_address?: string | null;
    notes?: string | null;
    created_at?: string | null;
    customer?: {
        name: string;
        phone?: string | null;
        whatsapp?: string | null;
    } | null;
    items: Array<{
        id: number;
        name: string;
        quantity: number;
        unit_price: number;
    }>;
};

type Props = {
    orders: PendingDeliveryOrder[];
    onRefresh: () => void;
};

export default function PosDeliveryQueue({ orders, onRefresh }: Props) {
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
            const result = await acceptDeliveryOrder(orderId);
            toast.success(result.message ?? 'تم قبول الطلب');
            onRefresh();
        } catch (error) {
            toast.error(error instanceof Error ? error.message : 'تعذر قبول الطلب');
        } finally {
            setProcessing(false);
        }
    };

    const handleReject = async () => {
        if (!selected) return;

        try {
            setProcessing(true);
            const result = await rejectDeliveryOrder(selected.id, rejectReason);
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
        <div className="flex min-h-0 flex-1 flex-col lg:flex-row">
            <div className="flex w-full shrink-0 flex-col border-b border-slate-200 bg-white lg:w-96 lg:border-b-0 lg:border-s dark:border-slate-800 dark:bg-slate-900">
                <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <div>
                        <h2 className="font-bold">طلبات بانتظار الموافقة</h2>
                        <p className="text-sm text-slate-500">{orders.length} طلب</p>
                    </div>
                    <Button variant="outline" size="sm" onClick={onRefresh} disabled={processing}>
                        <RefreshCw className="size-4" />
                    </Button>
                </div>

                <div className="max-h-64 flex-1 space-y-2 overflow-y-auto p-3 lg:max-h-none">
                    {orders.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 text-center text-slate-400">
                            <Inbox className="mb-3 size-12 opacity-40" strokeWidth={1.5} />
                            <p className="font-medium">لا توجد طلبات توصيل</p>
                            <p className="text-sm">ستظهر هنا عندما يطلب زبون من المتجر</p>
                        </div>
                    ) : (
                        orders.map((order) => (
                            <button
                                key={order.id}
                                type="button"
                                onClick={() => setSelectedId(order.id)}
                                className={cn(
                                    'w-full rounded-xl border p-4 text-right transition-all',
                                    selectedId === order.id
                                        ? 'border-emerald-400 bg-emerald-50 shadow-sm dark:bg-emerald-950/30'
                                        : 'border-slate-200 bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800/50',
                                )}
                            >
                                <div className="mb-2 flex items-start justify-between gap-2">
                                    <div>
                                        <p className="font-bold">#{order.order_number}</p>
                                        <p className="text-sm text-slate-600 dark:text-slate-300">
                                            {order.customer?.name ?? 'زبون'}
                                        </p>
                                    </div>
                                    <OrderStatusBadge status={order.status} />
                                </div>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-slate-500">
                                        {order.created_at ? formatDate(order.created_at) : ''}
                                    </span>
                                    <span className="font-bold text-emerald-600">
                                        {formatCurrency(order.total)}
                                    </span>
                                </div>
                            </button>
                        ))
                    )}
                </div>
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-4 dark:bg-slate-950">
                {!selected ? (
                    <div className="flex h-full items-center justify-center text-slate-400">
                        اختر طلباً لعرض التفاصيل
                    </div>
                ) : (
                    <div className="mx-auto max-w-2xl space-y-4">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 className="text-2xl font-bold">طلب #{selected.order_number}</h3>
                                    <p className="text-sm text-slate-500">
                                        {selected.created_at ? formatDate(selected.created_at) : ''}
                                    </p>
                                </div>
                                <OrderStatusBadge status={selected.status} />
                            </div>

                            <div className="mb-6 grid gap-3 sm:grid-cols-2">
                                <div className="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                                    <p className="mb-1 text-xs text-slate-500">العميل</p>
                                    <p className="font-semibold">{selected.customer?.name ?? '—'}</p>
                                    {(selected.customer?.phone || selected.customer?.whatsapp) && (
                                        <p className="mt-1 flex items-center gap-1 text-sm text-slate-600">
                                            <Phone className="size-3.5" />
                                            <span dir="ltr">
                                                {selected.customer.phone ?? selected.customer.whatsapp}
                                            </span>
                                        </p>
                                    )}
                                </div>
                                <div className="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                                    <p className="mb-1 text-xs text-slate-500">عنوان التوصيل</p>
                                    <p className="flex items-start gap-1 text-sm leading-relaxed">
                                        <MapPin className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                                        {selected.delivery_address ?? '—'}
                                    </p>
                                </div>
                            </div>

                            {selected.notes && (
                                <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                    ملاحظات: {selected.notes}
                                </div>
                            )}

                            <div className="mb-6 space-y-2">
                                <p className="text-sm font-medium text-slate-500">المنتجات</p>
                                {selected.items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2 dark:border-slate-800"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Badge variant="secondary">{item.quantity}×</Badge>
                                            <span className="font-medium">{item.name}</span>
                                        </div>
                                        <span>{formatCurrency(item.unit_price * item.quantity)}</span>
                                    </div>
                                ))}
                            </div>

                            <div className="mb-6 space-y-1 border-t pt-4 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-slate-500">المجموع الفرعي</span>
                                    <span>{formatCurrency(selected.subtotal ?? 0)}</span>
                                </div>
                                {(selected.delivery_fee ?? 0) > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">رسوم التوصيل</span>
                                        <span>{formatCurrency(selected.delivery_fee ?? 0)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between text-lg font-bold">
                                    <span>الإجمالي</span>
                                    <span className="text-emerald-600">{formatCurrency(selected.total)}</span>
                                </div>
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button
                                    size="lg"
                                    className="flex-1 bg-emerald-600 hover:bg-emerald-700"
                                    disabled={processing}
                                    onClick={() => handleAccept(selected.id)}
                                >
                                    {processing ? <Spinner /> : <Check className="size-5" />}
                                    قبول الطلب
                                </Button>
                                <Button
                                    size="lg"
                                    variant="destructive"
                                    className="flex-1"
                                    disabled={processing}
                                    onClick={() => setRejectOpen(true)}
                                >
                                    <X className="size-5" />
                                    رفض الطلب
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
                <DialogContent dir="rtl" className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>رفض الطلب</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="reject-reason">سبب الرفض (اختياري)</Label>
                        <textarea
                            id="reject-reason"
                            value={rejectReason}
                            onChange={(e) => setRejectReason(e.target.value)}
                            placeholder="مثال: المنطقة خارج نطاق التوصيل"
                            rows={3}
                            className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="ghost" onClick={() => setRejectOpen(false)} disabled={processing}>
                            إلغاء
                        </Button>
                        <Button variant="destructive" onClick={handleReject} disabled={processing}>
                            {processing && <Spinner />}
                            تأكيد الرفض
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
