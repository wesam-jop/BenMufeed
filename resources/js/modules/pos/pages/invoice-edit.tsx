import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Package, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import BusinessBrand from '@/components/domain/BusinessBrand';
import PosCart, { type PosCartItem } from '@/components/pos/pos-cart';
import PosProductCard from '@/components/pos/pos-product-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate, invoiceStatusVariant, statusLabel } from '@/lib/format';
import { cn } from '@/lib/utils';

type Product = {
    id: number;
    name: string;
    sale_price: number;
    image_url?: string | null;
    category_id?: number | null;
    category?: { id: number; name: string } | null;
};

type Category = { id: number; name: string };

type InvoiceItem = {
    product_id: number;
    product_name: string;
    quantity: number;
    unit_price: number;
};

type Invoice = {
    id: number;
    invoice_number?: string | null;
    status: string;
    total: number;
    created_at?: string | null;
    items: InvoiceItem[];
};

type Props = {
    invoice: Invoice;
    products?: Product[];
    categories?: Category[];
    warehouseId?: number | null;
};

function mapInvoiceToCart(items: InvoiceItem[]): PosCartItem[] {
    return items.map((item) => ({
        id: item.product_id,
        productId: item.product_id,
        name: item.product_name,
        price: item.unit_price,
        quantity: item.quantity,
    }));
}

export default function InvoiceEdit({
    invoice,
    products = [],
    categories = [],
    warehouseId = null,
}: Props) {
    const { t } = useI18n();

    const [search, setSearch] = useState('');
    const [categoryId, setCategoryId] = useState<number | 'all'>('all');
    const [cart, setCart] = useState<PosCartItem[]>(() => mapInvoiceToCart(invoice.items));
    const [processing, setProcessing] = useState(false);

    const filteredProducts = useMemo(() => {
        return products.filter((product) => {
            const matchesSearch = product.name.includes(search);
            const matchesCategory = categoryId === 'all' || product.category_id === categoryId;

            return matchesSearch && matchesCategory;
        });
    }, [products, search, categoryId]);

    const originalTotal = invoice.total;
    const newTotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

    const addToCart = (productId: number) => {
        const product = products.find((p) => p.id === productId);
        if (!product) return;

        setCart((prev) => {
            const existing = prev.find((i) => i.productId === productId);

            if (existing) {
                return prev.map((i) =>
                    i.productId === productId ? { ...i, quantity: i.quantity + 1 } : i,
                );
            }

            return [
                ...prev,
                {
                    id: productId,
                    productId,
                    name: product.name,
                    price: product.sale_price,
                    quantity: 1,
                },
            ];
        });
    };

    const handleCartIncrease = (itemId: number) => {
        setCart((prev) =>
            prev.map((i) => (i.id === itemId ? { ...i, quantity: i.quantity + 1 } : i)),
        );
    };

    const handleCartDecrease = (itemId: number) => {
        setCart((prev) =>
            prev
                .map((i) => (i.id === itemId && i.quantity > 1 ? { ...i, quantity: i.quantity - 1 } : i))
                .filter((i) => !(i.id === itemId && i.quantity <= 1)),
        );
    };

    const handleCartRemove = (itemId: number) => {
        setCart((prev) => prev.filter((i) => i.id !== itemId));
    };

    const handleSave = () => {
        if (!warehouseId) {
            toast.error(t('toast.warehouse_missing'));
            return;
        }

        if (cart.length === 0) {
            toast.error(t('toast.invoice_needs_item'));
            return;
        }

        setProcessing(true);
        router.put(
            `/pos/invoices/${invoice.id}`,
            {
                items: cart.map((item) => ({
                    product_id: item.productId,
                    quantity: item.quantity,
                })),
            },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div className="flex h-screen flex-col bg-slate-100" dir="rtl">
            <Head title={`تعديل ${invoice.invoice_number ?? invoice.id}`} />

            <header className="shrink-0 border-b border-slate-800 bg-slate-900 text-white shadow-lg">
                <div className="flex flex-wrap items-center gap-3 px-4 py-3">
                    <BusinessBrand
                        iconWrapperClassName="bg-white/10"
                        textClassName="text-white [&_span]:text-white"
                    />

                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-lg font-bold">
                                تعديل #{invoice.invoice_number ?? invoice.id}
                            </h1>
                            <Badge variant={invoiceStatusVariant(invoice.status)}>
                                {statusLabel(invoice.status)}
                            </Badge>
                        </div>
                        {invoice.created_at && (
                            <p className="text-sm text-slate-400">{formatDate(invoice.created_at)}</p>
                        )}
                    </div>

                    <div className="hidden items-center gap-3 text-sm sm:flex">
                        <span className="text-slate-400">السابق: {formatCurrency(originalTotal)}</span>
                        <span className="font-semibold text-emerald-400">الجديد: {formatCurrency(newTotal)}</span>
                    </div>

                    <Button
                        variant="secondary"
                        size="sm"
                        className="gap-2 bg-white/10 text-white hover:bg-white/20"
                        asChild
                    >
                        <Link href="/pos/daily-summary">
                            <ArrowRight className="size-4" />
                            رجوع
                        </Link>
                    </Button>
                </div>
            </header>

            <main className="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div className="shrink-0 border-b border-slate-200 bg-white px-4 py-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="relative min-w-[200px] max-w-md flex-1">
                            <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                placeholder="بحث عن منتج..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="ps-9"
                            />
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                onClick={() => setCategoryId('all')}
                                className={cn(
                                    'rounded-md border px-3 py-1.5 text-sm font-medium transition-colors',
                                    categoryId === 'all'
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
                                )}
                            >
                                الكل
                            </button>
                            {categories.map((cat) => (
                                <button
                                    key={cat.id}
                                    type="button"
                                    onClick={() => setCategoryId(cat.id)}
                                    className={cn(
                                        'rounded-md border px-3 py-1.5 text-sm font-medium transition-colors',
                                        categoryId === cat.id
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
                                    )}
                                >
                                    {cat.name}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="flex min-h-0 flex-1 overflow-hidden bg-slate-50">
                    <div className="min-h-0 flex-1 overflow-y-auto p-3">
                        {filteredProducts.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-24 text-slate-400">
                                <Package className="mb-3 size-12 opacity-40" strokeWidth={1.5} />
                                <p>لا توجد منتجات</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5">
                                {filteredProducts.map((product) => (
                                    <PosProductCard
                                        key={product.id}
                                        id={product.id}
                                        name={product.name}
                                        price={product.sale_price}
                                        imageUrl={product.image_url}
                                        category={product.category?.name}
                                        onAdd={addToCart}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    <PosCart
                        mode="takeaway"
                        title={`تعديل #${invoice.invoice_number ?? invoice.id}`}
                        checkoutLabel="حفظ التعديلات"
                        items={cart}
                        processing={processing}
                        onIncrease={handleCartIncrease}
                        onDecrease={handleCartDecrease}
                        onRemove={handleCartRemove}
                        onCheckout={handleSave}
                    />
                </div>
            </main>
        </div>
    );
}
