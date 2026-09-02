import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Clock, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import OrderStatusBadge from '@/components/domain/OrderStatusBadge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

type SessionInfo = {
    id: number;
    table_name: string;
    expires_at?: string | null;
    remaining_seconds: number;
};

type OrderItem = {
    id: number;
    name: string;
    quantity: number;
    unit_price: number;
};

type SessionOrder = {
    id: number;
    order_number: string;
    status: string;
    total: number;
    notes?: string | null;
    created_at?: string | null;
    rejection_reason?: string | null;
    items: OrderItem[];
};

type Props = {
    business: { name: string; currency?: string };
    session: SessionInfo;
    orders: SessionOrder[];
};

const statusHints: Record<string, string> = {
    pending: 'بانتظار موافقة الكاشير',
    confirmed: 'تمت الموافقة — الطلب في المطبخ',
    preparing: 'قيد التحضير في المطبخ',
    ready: 'طلبك جاهز',
    completed: 'مكتمل',
    rejected: 'تم رفض الطلب',
    cancelled: 'ملغي',
};

export default function TableOrderOrders({ business, session, orders }: Props) {
    const currency = business.currency ?? 'SAR';
    const [remaining, setRemaining] = useState(session.remaining_seconds);

    useEffect(() => {
        const tick = window.setInterval(() => {
            setRemaining((s) => Math.max(0, s - 1));
        }, 1000);

        return () => window.clearInterval(tick);
    }, []);

    useEffect(() => {
        const interval = window.setInterval(() => {
            router.reload({ only: ['orders', 'session'] });
        }, 15_000);

        return () => window.clearInterval(interval);
    }, []);

    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;

    return (
        <div dir="rtl" lang="ar" className="min-h-screen bg-[#faf8f5]">
            <Head title={`طلباتي — ${session.table_name}`} />

            <header className="sticky top-0 z-40 border-b border-stone-200/80 bg-white/95 backdrop-blur-md">
                <div className="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/table-order/menu">
                            <ArrowRight className="size-4" />
                            القائمة
                        </Link>
                    </Button>
                    <div className="text-center">
                        <p className="font-bold text-stone-900">{session.table_name}</p>
                        <p className="text-xs text-stone-500">طلباتي</p>
                    </div>
                    <span
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
                            remaining < 600 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-900',
                        )}
                    >
                        <Clock className="size-3.5" />
                        {String(minutes).padStart(2, '0')}:{String(seconds).padStart(2, '0')}
                    </span>
                </div>
            </header>

            <div className="mx-auto max-w-3xl space-y-4 px-4 py-6">
                <div className="flex justify-end">
                    <Button
                        variant="outline"
                        size="sm"
                        className="border-stone-200"
                        onClick={() => router.reload({ only: ['orders', 'session'] })}
                    >
                        <RefreshCw className="size-4" />
                        تحديث
                    </Button>
                </div>

                {orders.length === 0 ? (
                    <div className="rounded-3xl bg-white py-16 text-center shadow-sm ring-1 ring-stone-200/80">
                        <p className="text-stone-600">لا توجد طلبات بعد</p>
                        <Button className="mt-4 bg-stone-900 hover:bg-stone-800" asChild>
                            <Link href="/table-order/menu">تصفّح القائمة</Link>
                        </Button>
                    </div>
                ) : (
                    orders.map((order) => (
                        <article
                            key={order.id}
                            className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-stone-200/80"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-bold text-stone-900">{order.order_number}</p>
                                    {order.created_at && (
                                        <p className="mt-0.5 text-xs text-stone-500">
                                            {formatDate(order.created_at)}
                                        </p>
                                    )}
                                </div>
                                <OrderStatusBadge status={order.status} />
                            </div>
                            <p className="mt-2 text-sm text-amber-900">
                                {statusHints[order.status] ?? order.status}
                            </p>
                            {order.rejection_reason && (
                                <p className="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                    السبب: {order.rejection_reason}
                                </p>
                            )}
                            <div className="mt-4 grid gap-2 border-t border-stone-100 pt-4">
                                {order.items.map((item) => (
                                    <div key={item.id} className="flex justify-between text-sm">
                                        <span>
                                            {item.name} × {item.quantity}
                                        </span>
                                        <span className="font-medium">
                                            {formatCurrency(item.unit_price * item.quantity, currency)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            <p className="mt-3 text-end font-bold">
                                الإجمالي: {formatCurrency(order.total, currency)}
                            </p>
                        </article>
                    ))
                )}
            </div>
        </div>
    );
}
