import { Head, router } from '@inertiajs/react';
import { Package, Search } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import PosCart, { type PosCartItem } from '@/components/pos/pos-cart';
import PosCheckoutDialog from '@/components/pos/pos-checkout-dialog';
import PosDeliveryQueue, { type PendingDeliveryOrder } from '@/components/pos/pos-delivery-queue';
import PosProductCard from '@/components/pos/pos-product-card';
import PosShell from '@/components/pos/pos-shell';
import PosTableGrid, { type PosTable } from '@/components/pos/pos-table-grid';
import PosTableOrderQueue, { type PendingTableOrder } from '@/components/pos/pos-table-order-queue';
import { Input } from '@/components/ui/input';
import {
    addOrderItem,
    completeOrder,
    createOrder,
    openTableOrder,
    removeOrderItem,
    sendOrderToKitchen,
    updateOrderItem,
    type PosOrder,
} from '@/lib/pos-api';
import { cn } from '@/lib/utils';
import type { PosSaleMode } from '@/components/pos/pos-mode-tabs';

type Product = {
    id: number;
    name: string;
    sale_price: number;
    image_url?: string | null;
    category_id?: number | null;
    category?: { id: number; name: string } | null;
};

type Category = { id: number; name: string };

type DailySession = {
    session_date: string;
    status: string;
    register_name?: string | null;
    invoice_count: number;
    total_sales: number;
};

type Props = {
    branchId?: number | null;
    products?: Product[];
    categories?: Category[];
    warehouseId?: number | null;
    tables?: PosTable[];
    pendingDeliveryOrders?: PendingDeliveryOrder[];
    pendingTableOrders?: PendingTableOrder[];
    dailySession?: DailySession | null;
    initialMode?: PosSaleMode;
};

function parseMode(value: string | undefined | null): PosSaleMode {
    if (value === 'delivery' || value === 'tables' || value === 'table-qr') return value;
    return 'takeaway';
}

function mapOrderToCart(order: PosOrder): PosCartItem[] {
    return (order.items ?? []).map((item) => ({
        id: item.id,
        productId: item.product_id,
        orderItemId: item.id,
        name: item.product?.name ?? 'منتج',
        price: Number(item.unit_price),
        quantity: Number(item.quantity),
    }));
}

function applyOrderState(
    order: PosOrder,
    setCart: (items: PosCartItem[]) => void,
    setTableOrderId: (id: number) => void,
    setTableOrderTotal: (total: number) => void,
) {
    setTableOrderId(order.id);
    setTableOrderTotal(Number(order.total));
    setCart(mapOrderToCart(order));
}

export default function PosTerminal({
    products = [],
    categories = [],
    warehouseId = null,
    tables = [],
    pendingDeliveryOrders = [],
    pendingTableOrders = [],
    dailySession = null,
    initialMode = 'takeaway',
}: Props) {
    const { t } = useI18n();

    const [mode, setMode] = useState<PosSaleMode>(() => parseMode(initialMode));
    const [search, setSearch] = useState('');
    const [categoryId, setCategoryId] = useState<number | 'all'>('all');
    const [cart, setCart] = useState<PosCartItem[]>([]);
    const [processing, setProcessing] = useState(false);
    const [checkoutOpen, setCheckoutOpen] = useState(false);
    const [selectedTable, setSelectedTable] = useState<PosTable | null>(null);
    const [tableSessionId, setTableSessionId] = useState<number | null>(null);
    const [tableOrderId, setTableOrderId] = useState<number | null>(null);
    const [tableOrderTotal, setTableOrderTotal] = useState(0);

    const isTableMode = mode === 'tables';

    const switchMode = useCallback((next: PosSaleMode) => {
        setMode(next);
        setCart([]);
        setSelectedTable(null);
        setTableSessionId(null);
        setTableOrderId(null);
        setTableOrderTotal(0);
        router.visit(`/pos?mode=${next}`, { preserveState: true, preserveScroll: true, replace: true });
    }, []);

    useEffect(() => {
        if (mode !== 'delivery' && mode !== 'table-qr') return;

        const only =
            mode === 'delivery' ? (['pendingDeliveryOrders'] as const) : (['pendingTableOrders'] as const);

        const interval = setInterval(() => {
            router.reload({ only: [...only] });
        }, 30_000);

        return () => clearInterval(interval);
    }, [mode]);

    const filteredProducts = useMemo(() => {
        return products.filter((product) => {
            const matchesSearch = product.name.includes(search);
            const matchesCategory = categoryId === 'all' || product.category_id === categoryId;
            return matchesSearch && matchesCategory;
        });
    }, [products, search, categoryId]);

    const cartTotal = useMemo(() => {
        if (isTableMode && tableOrderTotal > 0) {
            return tableOrderTotal;
        }

        return cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    }, [cart, isTableMode, tableOrderTotal]);

    const ensureTableOrderId = async (): Promise<number> => {
        if (tableOrderId) {
            return tableOrderId;
        }

        if (!tableSessionId) {
            throw new Error('لم يتم فتح الطاولة');
        }

        const { order } = await createOrder({
            type: 'dine_in',
            table_session_id: tableSessionId,
        });

        setTableOrderId(order.id);
        return order.id;
    };

    const syncTableOrder = (order: PosOrder) => {
        applyOrderState(order, setCart, setTableOrderId, setTableOrderTotal);
    };

    const addToCart = async (productId: number) => {
        const product = products.find((p) => p.id === productId);
        if (!product) return;

        if (isTableMode) {
            if (!tableSessionId) {
                toast.error(t('toast.select_table_first'));
                return;
            }

            try {
                setProcessing(true);
                const orderId = await ensureTableOrderId();
                const result = await addOrderItem(orderId, { product_id: productId, quantity: 1 });
                syncTableOrder(result.order);
            } catch (error) {
                toast.error(error instanceof Error ? error.message : t('toast.add_product_failed'));
            } finally {
                setProcessing(false);
            }

            return;
        }

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

    const updateTableItemQuantity = async (orderItemId: number, quantity: number) => {
        if (!tableOrderId) return;

        try {
            setProcessing(true);
            const result = await updateOrderItem(tableOrderId, orderItemId, { quantity });
            syncTableOrder(result.order);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : t('toast.qty_update_failed'));
        } finally {
            setProcessing(false);
        }
    };

    const removeTableItem = async (orderItemId: number) => {
        if (!tableOrderId) return;

        try {
            setProcessing(true);
            const result = await removeOrderItem(tableOrderId, orderItemId);
            syncTableOrder(result.order);
        } catch (error) {
            toast.error(error instanceof Error ? error.message : t('toast.remove_product_failed'));
        } finally {
            setProcessing(false);
        }
    };

    const handleCartIncrease = (itemId: number) => {
        if (isTableMode) {
            const item = cart.find((i) => i.id === itemId);
            if (item?.orderItemId) {
                void updateTableItemQuantity(item.orderItemId, item.quantity + 1);
            }
            return;
        }

        setCart((prev) =>
            prev.map((i) => (i.id === itemId ? { ...i, quantity: i.quantity + 1 } : i)),
        );
    };

    const handleCartDecrease = (itemId: number) => {
        if (isTableMode) {
            const item = cart.find((i) => i.id === itemId);
            if (!item?.orderItemId) return;

            if (item.quantity <= 1) {
                void removeTableItem(item.orderItemId);
                return;
            }

            void updateTableItemQuantity(item.orderItemId, item.quantity - 1);
            return;
        }

        setCart((prev) =>
            prev
                .map((i) => (i.id === itemId && i.quantity > 1 ? { ...i, quantity: i.quantity - 1 } : i))
                .filter((i) => !(i.id === itemId && i.quantity <= 1)),
        );
    };

    const handleCartRemove = (itemId: number) => {
        if (isTableMode) {
            const item = cart.find((i) => i.id === itemId);
            if (item?.orderItemId) {
                void removeTableItem(item.orderItemId);
            }
            return;
        }

        setCart((prev) => prev.filter((i) => i.id !== itemId));
    };

    const selectTable = async (table: PosTable) => {
        try {
            setProcessing(true);
            const data = await openTableOrder(table.id);
            setSelectedTable(table);
            setTableSessionId(data.session.id);

            if (data.order) {
                syncTableOrder(data.order);
            } else {
                setTableOrderId(null);
                setTableOrderTotal(0);
                setCart([]);
            }
        } catch (error) {
            toast.error(error instanceof Error ? error.message : t('toast.open_table_failed'));
        } finally {
            setProcessing(false);
        }
    };

    const syncOrderItems = async (orderId: number): Promise<number> => {
        let total = 0;
        for (const item of cart) {
            const result = await addOrderItem(orderId, { product_id: item.id, quantity: item.quantity });
            total = result.order.total;
        }
        return total;
    };

    const buildOrderPayload = () => {
        if (mode === 'takeaway') {
            return { type: 'takeaway' };
        }

        return {
            type: 'dine_in',
            table_session_id: tableSessionId,
        };
    };

    const validateBeforeSubmit = (): boolean => {
        if (mode === 'tables' && !tableSessionId) {
            toast.error(t('toast.select_table_first'));
            return false;
        }

        if (!warehouseId) {
            toast.error(t('toast.warehouse_missing'));
            return false;
        }

        return true;
    };

    const handleSendToKitchen = async () => {
        if (!validateBeforeSubmit() || cart.length === 0) return;

        try {
            setProcessing(true);
            const orderId = await ensureTableOrderId();
            await sendOrderToKitchen(orderId);
            toast.success(t('toast.order_sent_kitchen'));
        } catch (error) {
            toast.error(error instanceof Error ? error.message : t('toast.send_order_failed'));
        } finally {
            setProcessing(false);
        }
    };

    const handleCheckout = () => {
        if (!validateBeforeSubmit() || cart.length === 0) return;
        setCheckoutOpen(true);
    };

    const handlePayment = async () => {
        if (!warehouseId) return;

        try {
            setProcessing(true);

            if (isTableMode) {
                const orderId = await ensureTableOrderId();
                await completeOrder(orderId, {
                    warehouse_id: warehouseId,
                    payments: [{ method: 'cash', amount: cartTotal }],
                });

                toast.success(t('toast.payment_done'));
                setCheckoutOpen(false);
                setCart([]);
                setSelectedTable(null);
                setTableSessionId(null);
                setTableOrderId(null);
                setTableOrderTotal(0);
                router.reload({ only: ['dailySession', 'tables'] });
                return;
            }

            const { order } = await createOrder(buildOrderPayload());
            const orderTotal = await syncOrderItems(order.id);
            await completeOrder(order.id, {
                warehouse_id: warehouseId,
                payments: [{ method: 'cash', amount: orderTotal }],
            });

            toast.success(t('toast.operation_success'));
            setCheckoutOpen(false);
            setCart([]);
            router.reload({ only: ['dailySession'] });
        } catch (error) {
            toast.error(error instanceof Error ? error.message : t('toast.payment_failed'));
        } finally {
            setProcessing(false);
        }
    };

    const showTablePicker = mode === 'tables' && !selectedTable;
    const isSalesMode = mode === 'takeaway' || mode === 'tables';

    return (
        <>
            <Head title="نقطة البيع" />
            <PosShell
                mode={mode}
                onModeChange={switchMode}
                dailySession={dailySession}
            >
                {mode === 'delivery' ? (
                    <PosDeliveryQueue
                        orders={pendingDeliveryOrders}
                        onRefresh={() => router.reload({ only: ['pendingDeliveryOrders'] })}
                    />
                ) : mode === 'table-qr' ? (
                    <PosTableOrderQueue
                        orders={pendingTableOrders}
                        onRefresh={() => router.reload({ only: ['pendingTableOrders'] })}
                    />
                ) : (
                    <main className="flex min-h-0 flex-1 flex-col overflow-hidden">
                        {!showTablePicker && (
                            <div className="shrink-0 border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
                                <div className="flex flex-wrap items-center gap-3">
                                    {mode === 'tables' && selectedTable && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSelectedTable(null);
                                                setTableSessionId(null);
                                                setTableOrderId(null);
                                                setTableOrderTotal(0);
                                                setCart([]);
                                            }}
                                            className="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
                                        >
                                            ← تغيير الطاولة
                                        </button>
                                    )}
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
                        )}

                        <div className="flex min-h-0 flex-1 overflow-hidden bg-slate-50">
                            <div className="min-h-0 flex-1 overflow-y-auto p-3">
                                {showTablePicker ? (
                                    <div>
                                        <h2 className="mb-4 text-xl font-bold text-slate-800 dark:text-white">
                                            اختر الطاولة
                                        </h2>
                                        <PosTableGrid
                                            tables={tables}
                                            selectedId={selectedTable?.id}
                                            onSelect={selectTable}
                                            disabled={processing}
                                        />
                                    </div>
                                ) : filteredProducts.length === 0 ? (
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

                            {!showTablePicker && isSalesMode && (
                                <PosCart
                                    mode={mode}
                                    items={cart}
                                    tableName={selectedTable?.name}
                                    processing={processing}
                                    onIncrease={handleCartIncrease}
                                    onDecrease={handleCartDecrease}
                                    onRemove={handleCartRemove}
                                    onCheckout={handleCheckout}
                                    onSendToKitchen={mode === 'tables' ? handleSendToKitchen : undefined}
                                />
                            )}
                        </div>
                    </main>
                )}
            </PosShell>

            <PosCheckoutDialog
                open={checkoutOpen}
                total={cartTotal}
                processing={processing}
                onClose={() => setCheckoutOpen(false)}
                onConfirm={handlePayment}
            />
        </>
    );
}
