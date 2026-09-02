import {
    Check,
    ChefHat,
    Clock,
    MapPin,
    ShoppingBag,
    Truck,
    UtensilsCrossed,
    User,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    formatCurrency,
    formatDate,
    formatQuantity,
    orderStatusLabel,
    orderTypeKitchenStyle,
    orderTypeLabel,
    statusLabel,
} from '@/lib/format';
import { cn } from '@/lib/utils';

type OrderItem = {
    id: number;
    name: string;
    quantity: number;
    status?: string;
    notes?: string | null;
};

type Props = {
    orderNumber: string | number;
    orderType: string;
    status: string;
    createdAt: string;
    tableName?: string | null;
    customerName?: string | null;
    notes?: string | null;
    items: OrderItem[];
    total?: number;
    processing?: boolean;
    onReady?: () => void;
};

const typeIcons: Record<string, typeof Truck> = {
    delivery: Truck,
    online: Truck,
    takeaway: ShoppingBag,
    dine_in: UtensilsCrossed,
};

export default function KitchenOrderCard({
    orderNumber,
    orderType,
    status,
    createdAt,
    tableName,
    customerName,
    notes,
    items,
    total,
    processing = false,
    onReady,
}: Props) {
    const style = orderTypeKitchenStyle(orderType);
    const TypeIcon = typeIcons[orderType] ?? ChefHat;
    const typeLabel = orderTypeLabel(orderType);

    return (
        <article className={cn('overflow-hidden rounded-2xl border-2 shadow-sm', style.card)}>
            <div className={cn('flex items-center justify-between gap-3 px-4 py-3 text-white', style.badge)}>
                <div className="flex items-center gap-2">
                    <TypeIcon className="size-5" />
                    <span className="text-lg font-bold">{typeLabel}</span>
                </div>
                <Badge variant="secondary" className="bg-white/20 text-white">
                    {orderStatusLabel(status)}
                </Badge>
            </div>

            <div className="space-y-4 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-2xl font-black tracking-tight">#{orderNumber}</p>
                        <p className="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                            <Clock className="size-3.5" />
                            {formatDate(createdAt)}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    {orderType === 'dine_in' && tableName && (
                        <span className={cn('inline-flex items-center gap-1 rounded-lg bg-white/80 px-2.5 py-1 text-sm font-semibold', style.accent)}>
                            <UtensilsCrossed className="size-3.5" />
                            طاولة {tableName}
                        </span>
                    )}
                    {(orderType === 'delivery' || orderType === 'online') && customerName && (
                        <span className={cn('inline-flex items-center gap-1 rounded-lg bg-white/80 px-2.5 py-1 text-sm font-semibold', style.accent)}>
                            <User className="size-3.5" />
                            {customerName}
                        </span>
                    )}
                    {orderType === 'takeaway' && (
                        <span className={cn('inline-flex items-center gap-1 rounded-lg bg-white/80 px-2.5 py-1 text-sm font-semibold', style.accent)}>
                            <ShoppingBag className="size-3.5" />
                            استلام من المحل
                        </span>
                    )}
                    {(orderType === 'delivery' || orderType === 'online') && (
                        <span className={cn('inline-flex items-center gap-1 rounded-lg bg-white/80 px-2.5 py-1 text-sm font-semibold', style.accent)}>
                            <MapPin className="size-3.5" />
                            توصيل
                        </span>
                    )}
                </div>

                {notes && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
                        <span className="font-medium">ملاحظات: </span>
                        {notes}
                    </div>
                )}

                <div className="space-y-2 rounded-xl bg-white/70 p-3">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        الأصناف
                    </p>
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="flex items-start justify-between gap-3 border-b border-slate-100 pb-2 last:border-0 last:pb-0"
                        >
                            <div>
                                <p className="text-base font-bold">
                                    <span className="me-1.5 inline-flex min-w-7 items-center justify-center rounded-md bg-slate-900 px-1.5 py-0.5 text-sm text-white">
                                        {formatQuantity(item.quantity)}
                                    </span>
                                    {item.name}
                                </p>
                                {item.notes && (
                                    <p className="mt-0.5 text-xs text-muted-foreground">{item.notes}</p>
                                )}
                            </div>
                            {item.status && item.status !== 'pending' && (
                                <Badge variant="outline" className="shrink-0 text-xs">
                                    {statusLabel(item.status)}
                                </Badge>
                            )}
                        </div>
                    ))}
                </div>

                <div className="flex items-center justify-between gap-3 border-t pt-3">
                    {total != null && (
                        <span className="text-lg font-bold text-emerald-700">{formatCurrency(total)}</span>
                    )}
                    {onReady && status !== 'ready' && (
                        <Button
                            size="lg"
                            className="ms-auto min-w-28 bg-slate-900 hover:bg-slate-800"
                            disabled={processing}
                            onClick={onReady}
                        >
                            {processing ? (
                                <Spinner />
                            ) : (
                                <>
                                    <Check className="size-4" />
                                    جاهز
                                </>
                            )}
                        </Button>
                    )}
                    {status === 'ready' && (
                        <Badge className="ms-auto bg-emerald-600 text-white">جاهز للتسليم</Badge>
                    )}
                </div>
            </div>
        </article>
    );
}
