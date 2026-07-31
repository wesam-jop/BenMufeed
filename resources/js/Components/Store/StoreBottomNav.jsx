import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { IconWhatsApp } from '@/Components/Store/icons/SocialIcons';
import { cartTotalQuantity, readCart } from '@/lib/cart';
import { whatsappHref } from '@/lib/whatsapp';

function IconHome({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden>
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" strokeLinejoin="round" />
        </svg>
    );
}

function IconLayers({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden>
            <path d="M12 2 2 7l10 5 10-5-10-5Z" strokeLinejoin="round" />
            <path d="m2 17 10 5 10-5M2 12l10 5 10-5" strokeLinejoin="round" />
        </svg>
    );
}

function IconGrid({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden>
            <rect x="3" y="3" width="7" height="7" rx="1.2" />
            <rect x="14" y="3" width="7" height="7" rx="1.2" />
            <rect x="3" y="14" width="7" height="7" rx="1.2" />
            <rect x="14" y="14" width="7" height="7" rx="1.2" />
        </svg>
    );
}

function IconCart({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden>
            <path d="M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="9" cy="20" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="18" cy="20" r="1.5" fill="currentColor" stroke="none" />
        </svg>
    );
}

const navItem =
    'flex min-w-0 flex-1 flex-col items-center justify-center gap-0.5 rounded-xl px-1 py-2 text-[10px] font-semibold transition-all duration-fast ease-smooth sm:text-[11px]';

export default function StoreBottomNav() {
    const { url } = usePage();
    const site = usePage().props.site;
    const waHref = whatsappHref(site?.whatsapp_phone);

    const [qty, setQty] = useState(0);

    useEffect(() => {
        const sync = () => setQty(cartTotalQuantity(readCart()));
        sync();
        window.addEventListener('kinju:cart-updated', sync);
        window.addEventListener('storage', sync);

        return () => {
            window.removeEventListener('kinju:cart-updated', sync);
            window.removeEventListener('storage', sync);
        };
    }, []);

    const path = (url.split('?')[0] || '/').split('#')[0] || '/';
    const homeActive = path === '/';
    const categoriesActive = path === '/categories' || path.startsWith('/categories/');
    const productsActive = path.startsWith('/products');
    const cartActive = path.startsWith('/cart');
    const accountActive = path.startsWith('/account');

    const iconClass = 'h-5 w-5 shrink-0 sm:h-5 sm:w-5';

    const activeClass = 'bg-store-secondary/15 text-store-secondary';
    const idleClass = 'text-store-surface/50 hover:bg-white/[0.04] hover:text-store-surface/90';

    return (
        <nav className="store-floating-nav sm:hidden" aria-label="التنقل الرئيسي">
            <div className="flex items-stretch justify-between gap-0.5 px-1.5 py-1.5">
                <Link
                    href="/"
                    className={`${navItem} ${homeActive ? activeClass : idleClass}`}
                    aria-current={homeActive ? 'page' : undefined}
                >
                    <IconHome className={iconClass} />
                    <span>الرئيسية</span>
                </Link>
                <Link
                    href="/categories"
                    className={`${navItem} ${categoriesActive ? activeClass : idleClass}`}
                    aria-current={categoriesActive ? 'page' : undefined}
                >
                    <IconLayers className={iconClass} />
                    <span>الأقسام</span>
                </Link>
                <Link
                    href="/products"
                    className={`${navItem} ${productsActive ? activeClass : idleClass}`}
                    aria-current={productsActive ? 'page' : undefined}
                >
                    <IconGrid className={iconClass} />
                    <span>القائمة</span>
                </Link>
                <Link
                    href="/account"
                    className={`${navItem} ${accountActive ? activeClass : idleClass}`}
                    aria-current={accountActive ? 'page' : undefined}
                >
                    <svg className={iconClass} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden>
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 20c0-3.3 2.7-6 8-6s8 2.7 8 6" strokeLinecap="round" />
                    </svg>
                    <span>حسابي</span>
                </Link>
                <Link
                    href="/cart"
                    className={`${navItem} ${cartActive ? activeClass : idleClass}`}
                    aria-current={cartActive ? 'page' : undefined}
                >
                    <span className="relative inline-flex">
                        <IconCart className={iconClass} />
                        {qty > 0 ? (
                            <span className="absolute -end-1.5 -top-1.5 flex min-h-[1rem] min-w-[1rem] items-center justify-center rounded-full bg-store-secondary px-0.5 text-[9px] font-bold text-store-primary">
                                {qty > 99 ? '99+' : qty}
                            </span>
                        ) : null}
                    </span>
                    <span>طلبي</span>
                </Link>
                {waHref ? (
                    <a
                        href={waHref}
                        target="_blank"
                        rel="noreferrer"
                        className={`${navItem} ${idleClass}`}
                        aria-label="واتساب"
                    >
                        <IconWhatsApp className={`${iconClass} text-emerald-400`} />
                        <span className="text-emerald-300/90">واتساب</span>
                    </a>
                ) : null}
            </div>
        </nav>
    );
}
