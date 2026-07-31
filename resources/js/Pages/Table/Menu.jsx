import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import TableLayout from '@/Layouts/TableLayout';
import { formatPrice } from '@/lib/formatPrice';
import { calculateUnitPrice } from '@/lib/productOptions';
import {
    addTableCartItem,
    buildTableCartItem,
    readTableCart,
    tableCartCount,
    tableCartSubtotal,
} from '@/lib/tableCart';

function OptionsModal({ product, symbol, onClose }) {
    const variants = product.variants || [];
    const addons = product.addons || [];

    const [variantId, setVariantId] = useState(
        () => (variants.find((v) => v.is_default) || variants[0])?.id ?? null,
    );
    const [addonQty, setAddonQty] = useState({});

    const { unitPrice } = useMemo(
        () => calculateUnitPrice(variants, addons, variantId, addonQty),
        [variants, addons, variantId, addonQty],
    );

    const setQty = (addon, delta) => {
        setAddonQty((prev) => {
            const next = Math.max(0, Math.min(addon.max_quantity || 1, (prev[addon.id] || 0) + delta));

            return { ...prev, [addon.id]: next };
        });
    };

    const confirm = () => {
        const item = buildTableCartItem(product, variantId, addonQty);
        addTableCartItem(item, 1);
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/70 sm:items-center" onClick={onClose}>
            <div
                className="max-h-[85vh] w-full max-w-md overflow-y-auto rounded-t-3xl border border-white/10 bg-neutral-900 p-5 sm:rounded-3xl"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex items-start justify-between gap-3">
                    <h3 className="text-lg font-black text-white">{product.name}</h3>
                    <button type="button" onClick={onClose} className="text-white/50 hover:text-white" aria-label="إغلاق">
                        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" /></svg>
                    </button>
                </div>

                {variants.length > 0 ? (
                    <div className="mt-4">
                        <p className="mb-2 text-sm font-bold text-amber-300">اختر النوع</p>
                        <div className="space-y-2">
                            {variants.map((v) => (
                                <label
                                    key={v.id}
                                    className={`flex cursor-pointer items-center justify-between rounded-xl border px-4 py-3 text-sm transition ${
                                        variantId === v.id ? 'border-amber-400 bg-amber-400/10' : 'border-white/10 bg-white/5'
                                    }`}
                                >
                                    <span className="flex items-center gap-2 font-medium text-white">
                                        <input
                                            type="radio"
                                            name="variant"
                                            checked={variantId === v.id}
                                            onChange={() => setVariantId(v.id)}
                                            className="accent-amber-400"
                                        />
                                        {v.name}
                                    </span>
                                    <span className="font-bold text-white/80">{formatPrice(v.price, symbol)}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                ) : null}

                {addons.length > 0 ? (
                    <div className="mt-5">
                        <p className="mb-2 text-sm font-bold text-amber-300">إضافات</p>
                        <div className="space-y-2">
                            {addons.map((a) => {
                                const qty = addonQty[a.id] || 0;

                                return (
                                    <div key={a.id} className="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm">
                                        <div>
                                            <p className="font-medium text-white">{a.name}</p>
                                            <p className="text-xs text-white/50">+{formatPrice(a.price, symbol)}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <button type="button" onClick={() => setQty(a, -1)} className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-lg font-bold text-white disabled:opacity-30" disabled={qty === 0}>−</button>
                                            <span className="w-5 text-center font-bold text-white">{qty}</span>
                                            <button type="button" onClick={() => setQty(a, 1)} className="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-lg font-bold text-white disabled:opacity-30" disabled={qty >= (a.max_quantity || 1)}>+</button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ) : null}

                <button
                    type="button"
                    onClick={confirm}
                    className="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-3.5 text-base font-black text-neutral-950 transition hover:bg-amber-300"
                >
                    أضف للطلب — {formatPrice(unitPrice, symbol)}
                </button>
            </div>
        </div>
    );
}

export default function Menu({ table, products = [], categories = [] }) {
    const { site } = usePage().props;
    const symbol = site?.currency_symbol || '$';

    const [count, setCount] = useState(0);
    const [subtotal, setSubtotal] = useState(0);
    const [query, setQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState(null);
    const [modalProduct, setModalProduct] = useState(null);
    const [toast, setToast] = useState('');

    useEffect(() => {
        const sync = () => {
            const cart = readTableCart();
            setCount(tableCartCount(cart));
            setSubtotal(tableCartSubtotal(cart));
        };

        sync();
        window.addEventListener('bm:table-cart-updated', sync);

        return () => window.removeEventListener('bm:table-cart-updated', sync);
    }, []);

    useEffect(() => {
        const onAdd = (e) => {
            setToast(`تمت إضافة ${e.detail?.name || 'الطبق'}`);
            const timer = window.setTimeout(() => setToast(''), 1800);

            return () => window.clearTimeout(timer);
        };

        window.addEventListener('bm:table-cart-item-added', onAdd);

        return () => window.removeEventListener('bm:table-cart-item-added', onAdd);
    }, []);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();

        return products.filter((p) => {
            if (activeCategory && p.category?.slug !== activeCategory) {
                return false;
            }

            if (q && !p.name.toLowerCase().includes(q)) {
                return false;
            }

            return true;
        });
    }, [products, query, activeCategory]);

    const quickAdd = (p) => {
        if (p.has_options) {
            setModalProduct(p);

            return;
        }

        addTableCartItem(
            {
                product_id: p.id,
                name: p.name,
                price: p.price,
                image_url: p.image_url ?? null,
            },
            1,
        );
    };

    return (
        <TableLayout table={table}>
            <Head title="القائمة" />

            <div className="sticky top-[64px] z-30 -mx-4 bg-neutral-950/95 px-4 pb-3 pt-1 backdrop-blur">
                <input
                    type="search"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder="ابحث عن طبق..."
                    className="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-white/40 focus:border-amber-400/50 focus:outline-none"
                />

                {categories.length > 0 ? (
                    <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
                        <button
                            type="button"
                            onClick={() => setActiveCategory(null)}
                            className={`shrink-0 rounded-full px-4 py-1.5 text-xs font-bold transition ${
                                activeCategory === null ? 'bg-amber-400 text-neutral-950' : 'bg-white/10 text-white/70'
                            }`}
                        >
                            الكل
                        </button>
                        {categories.map((c) => (
                            <button
                                key={c.slug}
                                type="button"
                                onClick={() => setActiveCategory(c.slug)}
                                className={`shrink-0 rounded-full px-4 py-1.5 text-xs font-bold transition ${
                                    activeCategory === c.slug ? 'bg-amber-400 text-neutral-950' : 'bg-white/10 text-white/70'
                                }`}
                            >
                                {c.name}
                            </button>
                        ))}
                    </div>
                ) : null}
            </div>

            {filtered.length === 0 ? (
                <p className="mt-12 text-center text-sm text-white/50">لا توجد أطباق مطابقة.</p>
            ) : (
                <div className="mt-4 grid grid-cols-2 gap-3">
                    {filtered.map((p) => (
                        <article key={p.id} className="flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-neutral-900/60">
                            <div className="aspect-square w-full overflow-hidden bg-white/5">
                                {p.image_url ? (
                                    <img src={p.image_url} alt={p.name} className="h-full w-full object-cover" loading="lazy" />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center text-xs text-white/30">لا صورة</div>
                                )}
                            </div>
                            <div className="flex flex-1 flex-col p-3">
                                <p className="line-clamp-2 min-h-[2.5rem] text-sm font-bold text-white">{p.name}</p>
                                <p className="mt-1 text-sm font-black text-amber-300">
                                    {p.has_options ? `من ${formatPrice(p.price, symbol)}` : formatPrice(p.price, symbol)}
                                </p>
                                <button
                                    type="button"
                                    onClick={() => quickAdd(p)}
                                    className="mt-auto rounded-xl bg-white/10 py-2 text-xs font-bold text-white transition hover:bg-amber-400 hover:text-neutral-950"
                                >
                                    {p.has_options ? 'اختر النوع' : 'أضف للطلب'}
                                </button>
                            </div>
                        </article>
                    ))}
                </div>
            )}

            {modalProduct ? (
                <OptionsModal product={modalProduct} symbol={symbol} onClose={() => setModalProduct(null)} />
            ) : null}

            {toast ? (
                <div className="fixed inset-x-0 bottom-24 z-50 mx-auto w-fit rounded-full bg-emerald-500 px-5 py-2 text-sm font-bold text-white shadow-lg">
                    {toast}
                </div>
            ) : null}

            {count > 0 ? (
                <div className="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-neutral-950/95 p-3 backdrop-blur">
                    <Link
                        href="/table/cart"
                        className="mx-auto flex max-w-3xl items-center justify-between rounded-xl bg-amber-400 px-5 py-3.5 text-neutral-950"
                    >
                        <span className="flex items-center gap-2 font-black">
                            <span className="flex h-6 min-w-6 items-center justify-center rounded-full bg-neutral-950 px-1.5 text-xs text-amber-300">{count}</span>
                            عرض الطلب
                        </span>
                        <span className="font-black">{formatPrice(subtotal, symbol)}</span>
                    </Link>
                </div>
            ) : null}
        </TableLayout>
    );
}
