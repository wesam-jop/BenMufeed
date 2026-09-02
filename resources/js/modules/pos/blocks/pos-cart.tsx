import { Minus, Plus, ShoppingCart, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency } from '@/lib/format';
import type { PosSaleMode } from './pos-mode-tabs';

export type PosCartItem = {
    id: number;
    productId: number;
    orderItemId?: number;
    name: string;
    price: number;
    quantity: number;
};

type Props = {
    mode: PosSaleMode;
    items: PosCartItem[];
    tableName?: string | null;
    title?: string;
    checkoutLabel?: string;
    disabled?: boolean;
    processing?: boolean;
    onIncrease: (id: number) => void;
    onDecrease: (id: number) => void;
    onRemove: (id: number) => void;
    onCheckout: () => void;
    onSendToKitchen?: () => void;
};

export default function PosCart({
    mode,
    items,
    tableName,
    title: titleOverride,
    checkoutLabel: checkoutLabelOverride,
    disabled = false,
    processing = false,
    onIncrease,
    onDecrease,
    onRemove,
    onCheckout,
    onSendToKitchen,
}: Props) {
    const subtotal = items.reduce((sum, item) => sum + item.price * item.quantity, 0);

    const modeTitles: Record<'takeaway' | 'tables', string> = {
        takeaway: 'طلب سفري',
        tables: tableName ?? 'طلب طاولة',
    };

    const title = titleOverride ?? (mode === 'tables' ? modeTitles.tables : modeTitles.takeaway);
    const checkoutLabel = checkoutLabelOverride ?? (mode === 'tables' ? 'تسديد الحساب' : 'إتمام الدفع');

    return (
        <aside className="flex w-full shrink-0 flex-col border-s border-slate-200 bg-white shadow-xl lg:w-96 dark:border-slate-800 dark:bg-slate-900">
            <div className="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 className="text-lg font-bold">{title}</h2>
                <p className="text-sm text-slate-500">
                    {items.reduce((sum, item) => sum + item.quantity, 0)} صنف
                </p>
            </div>

            <div className="flex-1 space-y-3 overflow-y-auto px-5 py-4">
                {items.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-16 text-center text-slate-400">
                        <ShoppingCart className="mb-3 size-10 opacity-50" strokeWidth={1.5} />
                        <p>السلة فارغة</p>
                        <p className="text-sm">اختر المنتجات من القائمة</p>
                    </div>
                ) : (
                    items.map((item) => (
                        <div key={item.id} className="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/50">
                            <div className="mb-2 flex items-start justify-between gap-2">
                                <div>
                                    <p className="font-medium">{item.name}</p>
                                    <p className="text-sm text-slate-500">{formatCurrency(item.price)}</p>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-8 shrink-0 text-red-500 hover:text-red-600"
                                    onClick={() => onRemove(item.id)}
                                    disabled={disabled}
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className="size-8"
                                    onClick={() => onDecrease(item.id)}
                                    disabled={disabled}
                                >
                                    <Minus className="size-4" />
                                </Button>
                                <span className="min-w-8 text-center font-bold">{item.quantity}</span>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className="size-8"
                                    onClick={() => onIncrease(item.id)}
                                    disabled={disabled}
                                >
                                    <Plus className="size-4" />
                                </Button>
                                <span className="ms-auto font-semibold text-emerald-600">
                                    {formatCurrency(item.price * item.quantity)}
                                </span>
                            </div>
                        </div>
                    ))
                )}
            </div>

            <div className="border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                <div className="mb-3 space-y-1.5 text-sm">
                    <div className="flex justify-between text-lg font-bold">
                        <span>الإجمالي</span>
                        <span className="text-emerald-600">{formatCurrency(subtotal)}</span>
                    </div>
                    <Separator />
                </div>

                <div className="flex flex-col gap-2">
                    {mode === 'tables' && onSendToKitchen && (
                        <Button
                            variant="outline"
                            size="lg"
                            className="w-full"
                            disabled={disabled || processing || items.length === 0}
                            onClick={onSendToKitchen}
                        >
                            {processing && <Spinner />}
                            إرسال للمطبخ
                        </Button>
                    )}
                    <Button
                        size="lg"
                        className="w-full bg-emerald-600 hover:bg-emerald-700"
                        disabled={disabled || processing || items.length === 0}
                        onClick={onCheckout}
                    >
                        {processing && <Spinner />}
                        {checkoutLabel}
                    </Button>
                </div>
            </div>
        </aside>
    );
}
