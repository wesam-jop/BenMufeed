import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import OrderStatusBadge from '@/components/domain/OrderStatusBadge';
import OrderTicket from '@/components/domain/OrderTicket';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';

type OrderItem = {
    id: number;
    quantity: number;
    unit_price?: number | string;
    product?: { name: string } | null;
};

type Order = {
    id: number;
    order_number?: string;
    status: string;
    created_at: string;
    total: number | string;
    subtotal?: number | string;
    tax_amount?: number | string;
    delivery_fee?: number | string;
    delivery_address?: string | null;
    notes?: string | null;
    rejection_reason?: string | null;
    items: OrderItem[];
};

type Props = {
    order: Order;
};

export default function CustomerOrderShow({ order }: Props) {
    return (
        <>
            <Head title={`طلب #${order.order_number ?? order.id}`} />

            <div className="mx-auto max-w-2xl space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <Button variant="ghost" size="sm" asChild className="mb-2 -me-2">
                            <Link href="/customer/orders">
                                <ArrowRight className="size-4" />
                                رجوع للطلبات
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold text-amber-950">
                            طلب #{order.order_number ?? order.id}
                        </h1>
                        <div className="mt-2 flex items-center gap-2">
                            <OrderStatusBadge status={order.status} />
                            <span className="text-sm text-muted-foreground">
                                {formatDate(order.created_at)}
                            </span>
                        </div>
                    </div>
                </div>

                {order.rejection_reason && (
                    <Card className="border-destructive/30 bg-destructive/5">
                        <CardContent className="pt-4 text-sm text-destructive">
                            سبب الرفض: {order.rejection_reason}
                        </CardContent>
                    </Card>
                )}

                <OrderTicket
                    orderNumber={order.order_number ?? order.id}
                    status={order.status}
                    createdAt={order.created_at}
                    total={Number(order.total)}
                    items={order.items.map((i) => ({
                        id: i.id,
                        name: i.product?.name ?? 'منتج',
                        quantity: i.quantity,
                        price: Number(i.unit_price ?? 0),
                    }))}
                />

                {order.delivery_address && (
                    <Card className="border-amber-100">
                        <CardContent className="space-y-1 pt-4 text-sm">
                            <p className="font-semibold">موقع التوصيل</p>
                            <p className="whitespace-pre-line text-muted-foreground">
                                {order.delivery_address}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {(order.subtotal != null || order.tax_amount != null || order.delivery_fee != null) && (
                    <Card className="border-amber-100">
                        <CardContent className="space-y-2 pt-4 text-sm">
                            {order.subtotal != null && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">المجموع الفرعي</span>
                                    <span>{formatCurrency(Number(order.subtotal))}</span>
                                </div>
                            )}
                            {order.tax_amount != null && Number(order.tax_amount) > 0 && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">الضريبة</span>
                                    <span>{formatCurrency(Number(order.tax_amount))}</span>
                                </div>
                            )}
                            {order.delivery_fee != null && Number(order.delivery_fee) > 0 && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">التوصيل</span>
                                    <span>{formatCurrency(Number(order.delivery_fee))}</span>
                                </div>
                            )}
                            <div className="flex justify-between border-t pt-2 font-bold">
                                <span>الإجمالي</span>
                                <span>{formatCurrency(Number(order.total))}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
