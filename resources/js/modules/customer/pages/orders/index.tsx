import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import OrderStatusBadge from '@/components/domain/OrderStatusBadge';
import PaginationLinks from '@/components/admin/pagination-links';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type Order = {
    id: number;
    order_number?: string;
    status: string;
    total: number | string;
    created_at: string;
    delivery_address?: string | null;
    items_count?: number;
};

type Props = {
    orders: Paginated<Order>;
};

export default function CustomerOrdersIndex({ orders }: Props) {
    return (
        <>
            <Head title="طلباتي" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-amber-950">طلباتي</h1>
                    <p className="text-muted-foreground">تابع حالة جميع طلباتك</p>
                </div>

                {orders.data.length === 0 ? (
                    <Card className="border-amber-100">
                        <CardContent className="py-12 text-center text-muted-foreground">
                            لا توجد طلبات حتى الآن
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {orders.data.map((order) => (
                            <Link key={order.id} href={`/customer/orders/${order.id}`}>
                                <Card className="border-amber-100 transition-colors hover:border-amber-200 hover:bg-amber-50/30">
                                    <CardContent className="flex items-center justify-between gap-4 p-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold">
                                                    #{order.order_number ?? order.id}
                                                </p>
                                                <OrderStatusBadge status={order.status} />
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {formatDate(order.created_at)}
                                            </p>
                                            {order.delivery_address && (
                                                <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
                                                    {order.delivery_address}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <p className="text-lg font-bold text-amber-900">
                                                {formatCurrency(Number(order.total))}
                                            </p>
                                            <ArrowRight className="size-4 text-muted-foreground" />
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}

                <PaginationLinks paginator={orders} />
            </div>
        </>
    );
}
