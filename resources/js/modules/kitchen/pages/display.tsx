import { Head, router } from '@inertiajs/react';
import { ChefHat, RefreshCw } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import KitchenOrderCard from '@/components/kitchen/kitchen-order-card';
import BusinessBrand from '@/components/domain/BusinessBrand';
import { Button } from '@/components/ui/button';
import { orderStatusLabel, orderTypeLabel } from '@/lib/format';
import { cn } from '@/lib/utils';

type OrderItem = {
    id: number;
    name: string;
    quantity: number;
    status?: string;
    notes?: string | null;
};

type Order = {
    id: number;
    order_number?: string | null;
    type: string;
    status: string;
    source?: string | null;
    notes?: string | null;
    created_at: string;
    total?: number;
    table_name?: string | null;
    customer_name?: string | null;
    items: OrderItem[];
};

type Props = {
    orders: Order[];
    status: string;
    statuses: string[];
};

const TYPE_FILTERS = [
    { value: 'all', label: 'الكل' },
    { value: 'delivery', label: 'توصيل' },
    { value: 'takeaway', label: 'سفري' },
    { value: 'dine_in', label: 'طاولة' },
] as const;

export default function KitchenDisplay({ orders, status, statuses }: Props) {

    const [typeFilter, setTypeFilter] = useState<string>('all');
    const [processingId, setProcessingId] = useState<number | null>(null);
    const [time, setTime] = useState(() => new Date());

    useEffect(() => {
        const clock = setInterval(() => setTime(new Date()), 30_000);
        return () => clearInterval(clock);
    }, []);

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['orders'] });
        }, 20_000);

        return () => clearInterval(interval);
    }, [status]);

    const filteredOrders = useMemo(() => {
        if (typeFilter === 'all') return orders;

        return orders.filter((order) => {
            if (typeFilter === 'delivery') {
                return order.type === 'delivery' || order.type === 'online';
            }

            return order.type === typeFilter;
        });
    }, [orders, typeFilter]);

    const countsByType = useMemo(() => {
        const counts = { all: orders.length, delivery: 0, takeaway: 0, dine_in: 0 };

        for (const order of orders) {
            if (order.type === 'delivery' || order.type === 'online') counts.delivery++;
            else if (order.type === 'takeaway') counts.takeaway++;
            else if (order.type === 'dine_in') counts.dine_in++;
        }

        return counts;
    }, [orders]);

    const markReady = useCallback((orderId: number) => {
        setProcessingId(orderId);
        router.patch(
            `/kitchen/orders/${orderId}/status`,
            { status: 'ready' },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    }, []);

    return (
        <div dir="rtl" className="min-h-screen bg-slate-100">
            <Head title="شاشة المطبخ" />

            <header className="border-b border-slate-800 bg-slate-900 text-white shadow-lg">
                <div className="flex flex-wrap items-center gap-4 px-4 py-3">
                    <BusinessBrand
                        iconWrapperClassName="bg-white/10"
                        textClassName="text-white [&_span]:text-white"
                    />

                    <div className="flex items-center gap-2">
                        <ChefHat className="size-6 text-amber-400" />
                        <div>
                            <h1 className="text-lg font-bold">شاشة المطبخ</h1>
                            <p className="text-xs text-slate-400">
                                {time.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' })}
                                {' · '}
                                {filteredOrders.length} طلب
                            </p>
                        </div>
                    </div>

                    <div className="ms-auto">
                        <Button
                            variant="secondary"
                            size="sm"
                            className="gap-2 bg-white/10 text-white hover:bg-white/20"
                            onClick={() => router.reload({ only: ['orders'] })}
                        >
                            <RefreshCw className="size-4" />
                            تحديث
                        </Button>
                    </div>
                </div>
            </header>

            <div className="border-b bg-white px-4 py-3 shadow-sm">
                <div className="mb-3 flex flex-wrap gap-2">
                    <span className="self-center text-sm font-medium text-muted-foreground">الحالة:</span>
                    {statuses.map((s) => (
                        <Button
                            key={s}
                            variant={s === status ? 'default' : 'outline'}
                            size="sm"
                            onClick={() =>
                                router.get('/kitchen', { status: s }, { preserveState: true, preserveScroll: true })
                            }
                        >
                            {orderStatusLabel(s)}
                        </Button>
                    ))}
                </div>

                <div className="flex flex-wrap gap-2">
                    <span className="self-center text-sm font-medium text-muted-foreground">نوع الطلب:</span>
                    {TYPE_FILTERS.map((filter) => (
                        <Button
                            key={filter.value}
                            variant={typeFilter === filter.value ? 'default' : 'outline'}
                            size="sm"
                            className={cn(
                                typeFilter === filter.value && filter.value === 'delivery' && 'bg-amber-500 hover:bg-amber-600',
                                typeFilter === filter.value && filter.value === 'takeaway' && 'bg-emerald-600 hover:bg-emerald-700',
                                typeFilter === filter.value && filter.value === 'dine_in' && 'bg-blue-600 hover:bg-blue-700',
                            )}
                            onClick={() => setTypeFilter(filter.value)}
                        >
                            {filter.label}
                            <span className="ms-1.5 rounded-full bg-white/20 px-1.5 text-xs">
                                {countsByType[filter.value]}
                            </span>
                        </Button>
                    ))}
                </div>
            </div>

            <main className="p-4">
                {filteredOrders.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-24 text-slate-400">
                        <ChefHat className="mb-3 size-16 opacity-30" />
                        <p className="text-lg font-medium">لا توجد طلبات</p>
                        <p className="text-sm">
                            {typeFilter === 'all'
                                ? `لا يوجد طلب بحالة "${orderStatusLabel(status)}"`
                                : `لا يوجد طلب ${orderTypeLabel(typeFilter === 'delivery' ? 'delivery' : typeFilter)}`}
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        {filteredOrders.map((order) => (
                            <KitchenOrderCard
                                key={order.id}
                                orderNumber={order.order_number ?? order.id}
                                orderType={order.type}
                                status={order.status}
                                createdAt={order.created_at}
                                tableName={order.table_name}
                                customerName={order.customer_name}
                                notes={order.notes}
                                total={order.total}
                                items={order.items}
                                processing={processingId === order.id}
                                onReady={() => markReady(order.id)}
                            />
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}
