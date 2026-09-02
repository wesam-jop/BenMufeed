import { Head, Link, router } from '@inertiajs/react';
import { Clock, Minus, Plus, Receipt, Send, ShoppingBag, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import ProductCard from '@/components/domain/ProductCard';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';

type Product = {
    id: number;
    name: string;
    description?: string | null;
    sale_price: number;
    image?: string | null;
    category?: string;
};

type Category = {
    id: number;
    name: string;
    products: Product[];
};

type SessionInfo = {
    id: number;
    table_name: string;
    expires_at?: string | null;
    remaining_seconds: number;
    guest_count?: number | null;
};

type SessionOrder = {
    id: number;
    order_number: string;
    status: string;
    total: number;
};

type CartLine = {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
};

type Props = {
    business: { name: string; currency?: string };
    session: SessionInfo;
    categories: Category[];
    orders: SessionOrder[];
};

export default function TableOrderMenu({ business, session, categories, orders }: Props) {
    const { t } = useI18n();
    const currency = business.currency ?? 'SAR';
    const [activeCategory, setActiveCategory] = useState<number | 'all'>('all');
    const [cart, setCart] = useState<CartLine[]>([]);
    const [remaining, setRemaining] = useState(session.remaining_seconds);
    const [cartOpen, setCartOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        const tick = window.setInterval(() => {
            setRemaining((s) => Math.max(0, s - 1));
        }, 1000);

        return () => window.clearInterval(tick);
    }, []);

    useEffect(() => {
        if (remaining === 0) {
            toast.error(t('toast.session_expired'));
            router.visit('/table-order');
        }
    }, [remaining]);

    const products = useMemo(() => {
        if (activeCategory === 'all') {
            return categories.flatMap((c) => c.products);
        }

        return categories.find((c) => c.id === activeCategory)?.products ?? [];
    }, [categories, activeCategory]);

    const cartCount = cart.reduce((sum, line) => sum + line.quantity, 0);
    const cartTotal = cart.reduce((sum, line) => sum + line.price * line.quantity, 0);

    const addToCart = (product: Product) => {
        setCart((prev) => {
            const existing = prev.find((l) => l.product_id === product.id);
            if (existing) {
                return prev.map((l) =>
                    l.product_id === product.id ? { ...l, quantity: l.quantity + 1 } : l,
                );
            }

            return [
                ...prev,
                {
                    product_id: product.id,
                    name: product.name,
                    price: product.sale_price,
                    quantity: 1,
                },
            ];
        });
        setCartOpen(true);
        toast.success(t('toast.product_added_named').replace(':name', product.name));
    };

    const updateQty = (productId: number, delta: number) => {
        setCart((prev) =>
            prev
                .map((l) =>
                    l.product_id === productId ? { ...l, quantity: l.quantity + delta } : l,
                )
                .filter((l) => l.quantity > 0),
        );
    };

    const submitOrder = () => {
        if (cart.length === 0) {
            toast.error(t('toast.cart_empty'));
            return;
        }

        setProcessing(true);
        router.post(
            '/table-order/orders',
            {
                items: cart.map((l) => ({ product_id: l.product_id, quantity: l.quantity })),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCart([]);
                    setCartOpen(false);
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;

    return (
        <div dir="rtl" lang="ar" className="min-h-screen bg-[#faf8f5] pb-28">
            <Head title={`قائمة ${session.table_name} — ${business.name}`} />

            <header className="sticky top-0 z-40 border-b border-stone-200/80 bg-white/95 backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p className="text-xs text-stone-500">{business.name}</p>
                        <h1 className="text-lg font-bold text-stone-900">{session.table_name}</h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <span
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium',
                                remaining < 600
                                    ? 'bg-rose-50 text-rose-700'
                                    : 'bg-amber-50 text-amber-900',
                            )}
                        >
                            <Clock className="size-4" />
                            {String(minutes).padStart(2, '0')}:{String(seconds).padStart(2, '0')}
                        </span>
                        <Button variant="outline" size="sm" className="border-stone-200" asChild>
                            <Link href="/table-order/orders">
                                <Receipt className="size-4" />
                                طلباتي
                                {orders.length > 0 && (
                                    <span className="ms-1 rounded-full bg-stone-900 px-1.5 text-[10px] text-white">
                                        {orders.length}
                                    </span>
                                )}
                            </Link>
                        </Button>
                    </div>
                </div>
                <div className="mx-auto flex max-w-6xl gap-2 overflow-x-auto px-4 pb-3">
                    <button
                        type="button"
                        onClick={() => setActiveCategory('all')}
                        className={cn(
                            'shrink-0 rounded-full px-4 py-1.5 text-sm font-medium',
                            activeCategory === 'all'
                                ? 'bg-stone-900 text-white'
                                : 'bg-stone-100 text-stone-700',
                        )}
                    >
                        الكل
                    </button>
                    {categories.map((cat) => (
                        <button
                            key={cat.id}
                            type="button"
                            onClick={() => setActiveCategory(cat.id)}
                            className={cn(
                                'shrink-0 rounded-full px-4 py-1.5 text-sm font-medium',
                                activeCategory === cat.id
                                    ? 'bg-stone-900 text-white'
                                    : 'bg-stone-100 text-stone-700',
                            )}
                        >
                            {cat.name}
                        </button>
                    ))}
                </div>
            </header>

            <div className="mx-auto grid max-w-6xl grid-cols-2 gap-4 px-4 py-6 sm:grid-cols-3 lg:grid-cols-4">
                {products.map((product) => (
                    <ProductCard
                        key={product.id}
                        id={product.id}
                        name={product.name}
                        price={product.sale_price}
                        image={product.image}
                        category={product.category}
                        description={product.description}
                        currency={currency}
                        onAdd={() => addToCart(product)}
                        addLabel="أضف"
                    />
                ))}
            </div>

            {cartCount > 0 && (
                <div className="fixed inset-x-0 bottom-0 z-50 border-t border-stone-200 bg-white p-4 shadow-2xl">
                    <div className="mx-auto flex max-w-6xl items-center gap-3">
                        <Button
                            variant="outline"
                            className="relative h-12 flex-1 border-stone-200"
                            onClick={() => setCartOpen((v) => !v)}
                        >
                            <ShoppingBag className="size-4" />
                            السلة ({cartCount})
                            <span className="ms-auto font-bold">{formatCurrency(cartTotal, currency)}</span>
                        </Button>
                        <Button
                            className="h-12 bg-stone-900 px-6 hover:bg-stone-800"
                            disabled={processing}
                            onClick={submitOrder}
                        >
                            <Send className="size-4" />
                            إرسال الطلب
                        </Button>
                    </div>

                    {cartOpen && (
                        <div className="mx-auto mt-3 max-w-6xl rounded-2xl border border-stone-200 bg-stone-50 p-4">
                            <div className="grid gap-3">
                                {cart.map((line) => (
                                    <div key={line.product_id} className="flex items-center gap-3">
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium">{line.name}</p>
                                            <p className="text-sm text-stone-500">
                                                {formatCurrency(line.price * line.quantity, currency)}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                size="icon"
                                                variant="outline"
                                                className="size-8"
                                                onClick={() => updateQty(line.product_id, -1)}
                                            >
                                                {line.quantity === 1 ? (
                                                    <Trash2 className="size-3.5" />
                                                ) : (
                                                    <Minus className="size-3.5" />
                                                )}
                                            </Button>
                                            <span className="w-6 text-center font-semibold">{line.quantity}</span>
                                            <Button
                                                size="icon"
                                                variant="outline"
                                                className="size-8"
                                                onClick={() => updateQty(line.product_id, 1)}
                                            >
                                                <Plus className="size-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <p className="mt-3 text-xs text-stone-500">
                                بعد الإرسال ينتظر الطلب موافقة الكاشير قبل الذهاب للمطبخ.
                            </p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
