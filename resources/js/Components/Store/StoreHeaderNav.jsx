import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { cartTotalQuantity, readCart } from '@/lib/cart';

export default function StoreHeaderNav() {
    const { url } = usePage();
    const path = (url.split('?')[0] || '/').split('#')[0] || '/';

    const [cartQty, setCartQty] = useState(0);

    useEffect(() => {
        const sync = () => setCartQty(cartTotalQuantity(readCart()));
        sync();
        window.addEventListener('kinju:cart-updated', sync);
        window.addEventListener('storage', sync);

        return () => {
            window.removeEventListener('kinju:cart-updated', sync);
            window.removeEventListener('storage', sync);
        };
    }, []);

    const is = (p) => path === p || (p !== '/' && path.startsWith(p));

    const items = [
        { href: '/', label: 'الرئيسية', active: is('/') && path === '/' },
        { href: '/categories', label: 'الأقسام', active: is('/categories') },
        { href: '/products', label: 'القائمة', active: is('/products') },
        { href: '/account', label: 'حسابي', active: is('/account') },
        {
            href: '/cart',
            label: 'طلبي',
            active: is('/cart'),
            badge: cartQty,
        },
    ];

    return (
        <nav
            className="hidden items-center justify-center gap-0.5 rounded-full border border-store-border bg-neutral-50/80 p-1 shadow-xs sm:flex"
            aria-label="قائمة الكافيتيريا"
        >
            {items.map((item) => (
                <Link
                    key={item.href}
                    href={item.href}
                    className={`store-nav-pill relative ${item.active ? 'store-nav-pill-active' : ''}`}
                    aria-current={item.active ? 'page' : undefined}
                >
                    {item.label}
                    {item.badge > 0 ? (
                        <span className="ms-1.5 inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-store-secondary px-1 text-[10px] font-bold text-store-primary">
                            {item.badge > 99 ? '99+' : item.badge}
                        </span>
                    ) : null}
                </Link>
            ))}
        </nav>
    );
}
