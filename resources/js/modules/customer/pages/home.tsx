import { Head, Link } from '@inertiajs/react';
import { ClipboardList, MapPin, ShoppingBag, Star } from 'lucide-react';
import OrderStatusBadge from '@/components/domain/OrderStatusBadge';
import StatCard from '@/components/domain/StatCard';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatCustomerAddress, formatDate } from '@/lib/format';

type Order = {
    id: number;
    order_number?: string;
    status: string;
    total: number | string;
    created_at: string;
    delivery_address?: string | null;
};

type Address = {
    id: number;
    label?: string | null;
    city?: string | null;
    street?: string | null;
    is_default?: boolean;
    formatted?: string;
};

type Props = {
    customer?: { name?: string | null; phone?: string };
    stats?: {
        orders_total: number;
        orders_active: number;
        addresses_total: number;
        loyalty_points: number;
    };
    recentOrders?: Order[];
    defaultAddress?: Address | null;
};

export default function CustomerHome({
    customer,
    stats = { orders_total: 0, orders_active: 0, addresses_total: 0, loyalty_points: 0 },
    recentOrders = [],
    defaultAddress = null,
}: Props) {
    return (
        <>
            <Head title="لوحة التحكم" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-amber-950">
                        مرحباً {customer?.name ?? 'بك'}
                    </h1>
                    <p className="text-muted-foreground">إدارة طلباتك ومواقع التوصيل من مكان واحد</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard title="إجمالي الطلبات" value={stats.orders_total} icon={ShoppingBag} format="number" />
                    <StatCard title="طلبات نشطة" value={stats.orders_active} icon={ClipboardList} format="number" />
                    <StatCard title="مواقع محفوظة" value={stats.addresses_total} icon={MapPin} format="number" />
                    <StatCard title="نقاط الولاء" value={stats.loyalty_points} icon={Star} format="number" />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card className="border-amber-100">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>آخر الطلبات</CardTitle>
                            <Button variant="link" size="sm" asChild className="h-auto p-0">
                                <Link href="/customer/orders">عرض الكل</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recentOrders.length === 0 ? (
                                <div className="py-6 text-center">
                                    <p className="text-muted-foreground">لا توجد طلبات بعد</p>
                                    <Button asChild className="mt-3 bg-amber-800 hover:bg-amber-900">
                                        <Link href="/products">اطلب الآن</Link>
                                    </Button>
                                </div>
                            ) : (
                                recentOrders.map((order) => (
                                    <Link
                                        key={order.id}
                                        href={`/customer/orders/${order.id}`}
                                        className="flex items-center justify-between rounded-lg border border-amber-50 p-3 transition-colors hover:bg-amber-50/50"
                                    >
                                        <div>
                                            <p className="font-medium">#{order.order_number ?? order.id}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(order.created_at)}
                                            </p>
                                        </div>
                                        <div className="text-left">
                                            <OrderStatusBadge status={order.status} />
                                            <p className="mt-1 text-sm font-bold">
                                                {formatCurrency(Number(order.total))}
                                            </p>
                                        </div>
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-amber-100">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>موقع التوصيل الافتراضي</CardTitle>
                            <Button variant="link" size="sm" asChild className="h-auto p-0">
                                <Link href="/customer/addresses">إدارة المواقع</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {defaultAddress ? (
                                <div className="space-y-2">
                                    <p className="font-medium">{defaultAddress.label ?? 'موقع التوصيل'}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {defaultAddress.formatted ?? formatCustomerAddress(defaultAddress)}
                                    </p>
                                </div>
                            ) : (
                                <div className="py-6 text-center">
                                    <p className="text-muted-foreground">لا يوجد موقع محفوظ</p>
                                    <Button variant="outline" asChild className="mt-3">
                                        <Link href="/customer/addresses">أضف موقعاً</Link>
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Button asChild className="bg-amber-800 hover:bg-amber-900">
                        <Link href="/products">اطلب الآن</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/customer/orders">طلباتي</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/customer/addresses">مواقع التوصيل</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
