import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import TableLayout from '@/Layouts/TableLayout';
import { formatPrice } from '@/lib/formatPrice';
import {
    clearTableCart,
    readTableCart,
    removeTableCartItem,
    setTableCartQuantity,
    tableCartSubtotal,
} from '@/lib/tableCart';

function readCookie(name) {
    if (typeof document === 'undefined') {
        return '';
    }

    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : '';
}

export default function Cart({ table, errors = {} }) {
    const { site } = usePage().props;
    const symbol = site?.currency_symbol || '$';

    const [cart, setCart] = useState({ items: [] });
    const [note, setNote] = useState('');
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        const sync = () => setCart(readTableCart());

        sync();
        window.addEventListener('bm:table-cart-updated', sync);

        return () => window.removeEventListener('bm:table-cart-updated', sync);
    }, []);

    const subtotal = tableCartSubtotal(cart);

    const submit = () => {
        if (submitting || cart.items.length === 0) {
            return;
        }

        setSubmitting(true);

        const payload = {
            items: cart.items.map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                product_variant_id: i.product_variant_id,
                addons: (i.addons || []).map((a) => ({
                    product_addon_id: a.product_addon_id,
                    quantity: a.quantity,
                })),
            })),
            table_session_token: readCookie('bm_tos') || null,
            customer_note: note || null,
        };

        router.post('/table/order', payload, {
            onSuccess: () => clearTableCart(),
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <TableLayout table={table}>
            <Head title="طلبك" />

            <div className="flex items-center justify-between">
                <h1 className="text-xl font-black text-white">طلبك</h1>
                <Link href="/table/menu" className="text-sm font-bold text-amber-300 hover:text-amber-200">
                    + أضف المزيد
                </Link>
            </div>

            {errors.items ? (
                <p className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-sm font-medium text-red-300">
                    {errors.items}
                </p>
            ) : null}
            {errors.table_token || errors.table_scan_session ? (
                <p className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-sm font-medium text-red-300">
                    {errors.table_token || errors.table_scan_session}
                </p>
            ) : null}

            {cart.items.length === 0 ? (
                <div className="mt-10 text-center">
                    <p className="text-sm text-white/50">سلّتك فارغة.</p>
                    <Link href="/table/menu" className="mt-4 inline-block rounded-xl bg-amber-400 px-5 py-2.5 text-sm font-black text-neutral-950">
                        تصفّح القائمة
                    </Link>
                </div>
            ) : (
                <>
                    <div className="mt-4 space-y-3">
                        {cart.items.map((i) => (
                            <div key={i.line_key} className="flex gap-3 rounded-2xl border border-white/10 bg-neutral-900/60 p-3">
                                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-white/5">
                                    {i.image_url ? (
                                        <img src={i.image_url} alt={i.name} className="h-full w-full object-cover" />
                                    ) : null}
                                </div>
                                <div className="flex flex-1 flex-col">
                                    <p className="text-sm font-bold text-white">{i.name}</p>
                                    <p className="text-sm font-black text-amber-300">{formatPrice(i.price, symbol)}</p>
                                    <div className="mt-auto flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <button type="button" onClick={() => setTableCartQuantity(i.line_key, i.quantity - 1)} className="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-lg font-bold text-white">−</button>
                                            <span className="w-5 text-center font-bold text-white">{i.quantity}</span>
                                            <button type="button" onClick={() => setTableCartQuantity(i.line_key, i.quantity + 1)} className="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-lg font-bold text-white">+</button>
                                        </div>
                                        <button type="button" onClick={() => removeTableCartItem(i.line_key)} className="text-xs font-medium text-red-400 hover:text-red-300">حذف</button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4">
                        <label className="mb-1.5 block text-sm font-bold text-white/80">ملاحظة (اختياري)</label>
                        <textarea
                            value={note}
                            onChange={(e) => setNote(e.target.value)}
                            rows={2}
                            placeholder="مثال: بدون سكر، إضافة ثلج..."
                            className="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-white/40 focus:border-amber-400/50 focus:outline-none"
                        />
                    </div>

                    <div className="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-neutral-950/95 p-3 backdrop-blur">
                        <div className="mx-auto flex max-w-3xl items-center gap-3">
                            <div className="text-white">
                                <p className="text-[11px] text-white/50">الإجمالي</p>
                                <p className="text-lg font-black text-amber-300">{formatPrice(subtotal, symbol)}</p>
                            </div>
                            <button
                                type="button"
                                onClick={submit}
                                disabled={submitting}
                                className="flex-1 rounded-xl bg-amber-400 px-5 py-3.5 text-base font-black text-neutral-950 transition hover:bg-amber-300 disabled:opacity-60"
                            >
                                {submitting ? 'جارٍ الإرسال...' : 'تأكيد الطلب'}
                            </button>
                        </div>
                    </div>
                </>
            )}
        </TableLayout>
    );
}
