import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { cartSubtotal, cartTotalQuantity, readCart } from '@/lib/cart';
import { formatPrice } from '@/lib/formatPrice';

function IconCart({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
            <path d="M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="9" cy="20" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="18" cy="20" r="1.5" fill="currentColor" stroke="none" />
        </svg>
    );
}

export default function StoreFloatingCart() {
    const { url } = usePage();
    const { site } = usePage().props;
    const symbol = site?.currency_symbol || '$';
    const path = ((url || '/').split('?')[0] || '/').split('#')[0] || '/';
    const onCartPage = path.startsWith('/cart');

    const [qty, setQty] = useState(0);
    const [subtotal, setSubtotal] = useState(0);
    const [pulse, setPulse] = useState(false);
    const [toast, setToast] = useState(null);
    const toastTimer = useRef(0);
    const pulseTimer = useRef(0);

    const sync = () => {
        const cart = readCart();
        setQty(cartTotalQuantity(cart));
        setSubtotal(cartSubtotal(cart));
    };

    useEffect(() => {
        sync();

        const onUpdate = () => sync();
        const onAdded = (event) => {
            sync();
            setPulse(true);
            window.clearTimeout(pulseTimer.current);
            pulseTimer.current = window.setTimeout(() => setPulse(false), 900);

            const name = event.detail?.name;
            if (name) {
                setToast(name);
                window.clearTimeout(toastTimer.current);
                toastTimer.current = window.setTimeout(() => setToast(null), 2800);
            }

            navigator.vibrate?.(30);
        };

        window.addEventListener('kinju:cart-updated', onUpdate);
        window.addEventListener('kinju:cart-item-added', onAdded);
        window.addEventListener('storage', onUpdate);

        return () => {
            window.removeEventListener('kinju:cart-updated', onUpdate);
            window.removeEventListener('kinju:cart-item-added', onAdded);
            window.removeEventListener('storage', onUpdate);
            window.clearTimeout(toastTimer.current);
            window.clearTimeout(pulseTimer.current);
        };
    }, []);

    if (qty <= 0 || onCartPage) {
        return null;
    }

    return (
        <div className="store-floating-cart" aria-live="polite">
            {toast ? (
                <div className="store-floating-cart__toast" role="status">
                    <span className="store-floating-cart__toast-check" aria-hidden>
                        ✓
                    </span>
                    <span className="min-w-0 truncate">تمت إضافة {toast}</span>
                </div>
            ) : null}

            <Link href="/cart" className={`store-floating-cart__btn ${pulse ? 'store-floating-cart__btn--pulse' : ''}`}>
                <span className="store-floating-cart__icon-wrap">
                    <IconCart className="h-6 w-6" />
                    <span className="store-floating-cart__badge" aria-label={`${qty} صنف في الطلب`}>
                        {qty > 99 ? '99+' : qty}
                    </span>
                </span>
                <span className="store-floating-cart__text">
                    <span className="store-floating-cart__label">طلبي</span>
                    <span className="store-floating-cart__total">{formatPrice(subtotal, symbol)}</span>
                </span>
            </Link>
        </div>
    );
}
