import { Minus, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatCurrency } from '@/lib/format';

export type CartItem = {
    id: number | string;
    name: string;
    quantity: number;
    price: number;
    notes?: string;
};

type CartPanelProps = {
    items: CartItem[];
    onIncrease?: (id: number | string) => void;
    onDecrease?: (id: number | string) => void;
    onRemove?: (id: number | string) => void;
    onCheckout?: () => void;
    checkoutLabel?: string;
    disabled?: boolean;
};

export default function CartPanel({
    items,
    onIncrease,
    onDecrease,
    onRemove,
    onCheckout,
    checkoutLabel = 'إتمام الدفع',
    disabled = false,
}: CartPanelProps) {
    const subtotal = items.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0,
    );

    return (
        <Card className="flex h-full flex-col">
            <CardHeader>
                <CardTitle>سلة الطلب</CardTitle>
            </CardHeader>
            <CardContent className="flex-1 space-y-3 overflow-y-auto">
                {items.length === 0 ? (
                    <p className="py-8 text-center text-sm text-muted-foreground">
                        السلة فارغة
                    </p>
                ) : (
                    items.map((item) => (
                        <div key={item.id} className="space-y-2">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <p className="font-medium">{item.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatCurrency(item.price)}
                                    </p>
                                </div>
                                {onRemove && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8 shrink-0"
                                        onClick={() => onRemove(item.id)}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                {onDecrease && (
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        className="size-8"
                                        onClick={() => onDecrease(item.id)}
                                    >
                                        <Minus className="size-4" />
                                    </Button>
                                )}
                                <span className="min-w-8 text-center font-medium">
                                    {item.quantity}
                                </span>
                                {onIncrease && (
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        className="size-8"
                                        onClick={() => onIncrease(item.id)}
                                    >
                                        <Plus className="size-4" />
                                    </Button>
                                )}
                                <span className="mr-auto text-sm font-semibold">
                                    {formatCurrency(item.price * item.quantity)}
                                </span>
                            </div>
                            <Separator />
                        </div>
                    ))
                )}
            </CardContent>
            <CardFooter className="flex-col gap-3 border-t pt-4">
                <div className="flex w-full items-center justify-between text-lg font-bold">
                    <span>الإجمالي</span>
                    <span>{formatCurrency(subtotal)}</span>
                </div>
                {onCheckout && (
                    <Button
                        className="w-full"
                        size="lg"
                        disabled={disabled || items.length === 0}
                        onClick={onCheckout}
                    >
                        {checkoutLabel}
                    </Button>
                )}
            </CardFooter>
        </Card>
    );
}
