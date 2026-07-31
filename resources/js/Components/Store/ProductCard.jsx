import { Link, usePage } from '@inertiajs/react';
import CachedImage from '@/Components/Store/CachedImage';
import { useRef, useState } from 'react';
import { upsertCartItem } from '@/lib/cart';
import { formatPrice } from '@/lib/formatPrice';

function IconCart({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
            <path d="M6 6h15l-1.5 9h-12L6 6Zm0 0L5 3H2" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="9" cy="20" r="1.25" fill="currentColor" stroke="none" />
            <circle cx="18" cy="20" r="1.25" fill="currentColor" stroke="none" />
        </svg>
    );
}

function IconCheck({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden>
            <path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

/**
 * @param {{ id: number, slug: string, name: string, price: number, compare_at_price?: number|null, image_url?: string|null }} product
 */
export default function ProductCard({ product: p, className = '' }) {
    const { site } = usePage().props;
    const symbol = site?.currency_symbol || '$';
    const [added, setAdded] = useState(false);
    const hideTimer = useRef(0);

    const addToCart = (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (p.has_options) {
            window.location.href = `/products/${p.slug}`;

            return;
        }

        upsertCartItem(
            {
                product_id: p.id,
                slug: p.slug,
                name: p.name,
                price: p.price,
                compare_at_price: p.compare_at_price ?? null,
                image_url: p.image_url ?? null,
            },
            1,
        );

        setAdded(true);
        const region = document.getElementById('cart-live-region');
        if (region) {
            region.textContent = `تمت إضافة ${p.name} إلى طلبك`;
        }
        window.clearTimeout(hideTimer.current);
        hideTimer.current = window.setTimeout(() => setAdded(false), 2200);
    };

    return (
        <article className={`store-card-interactive group flex h-full flex-col ${className}`}>
            <Link
                href={`/products/${p.slug}`}
                className="relative block overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-store-secondary/50"
            >
                <div className="aspect-square w-full overflow-hidden bg-gradient-to-b from-neutral-50 to-neutral-100">
                    {p.image_url ? (
                        <CachedImage
                            src={p.image_url}
                            alt={p.name}
                            className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center text-sm text-store-muted">
                            لا صورة
                        </div>
                    )}
                </div>
                {p.is_featured ? (
                    <span className="store-badge-warning absolute start-2 top-2 text-[10px]">
                        مميز
                    </span>
                ) : null}
                {p.is_delivery_available === false ? (
                    <span className="absolute end-2 top-2 rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-bold text-red-700">
                        غير متاح للتوصيل
                    </span>
                ) : null}
            </Link>

            <div className="flex flex-1 flex-col border-t border-store-border p-3 sm:p-4">
                <Link
                    href={`/products/${p.slug}`}
                    className="line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-snug text-store-primary transition hover:text-store-accent"
                >
                    {p.name}
                </Link>

                <div className="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <span className="text-base font-bold text-store-secondary sm:text-lg">
                        {p.has_options ? `من ${formatPrice(p.price, symbol)}` : formatPrice(p.price, symbol)}
                    </span>
                    {p.compare_at_price ? (
                        <span className="text-xs text-store-muted line-through">
                            {formatPrice(p.compare_at_price, symbol)}
                        </span>
                    ) : null}
                </div>
                {p.is_delivery_available === false ? (
                    <p className="mt-2 text-xs font-medium text-red-600">هذا الصنف غير متاح مع طلبات التوصيل.</p>
                ) : null}

                <div className="mt-auto pt-3">
                    <button
                        type="button"
                        onClick={addToCart}
                        aria-label={added ? 'تمت الإضافة للطلب' : `إضافة ${p.name} إلى الطلب`}
                        className={`flex w-full items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-caption font-bold transition-all duration-fast ease-smooth sm:text-body ${
                            added
                                ? 'bg-semantic-success text-white shadow-sm'
                                : 'bg-store-primary text-store-surface hover:bg-store-primary-hover active:scale-[0.98]'
                        }`}
                    >
                        {added ? (
                            <>
                                <IconCheck className="h-4 w-4 shrink-0" />
                                <span>تمت الإضافة</span>
                            </>
                        ) : (
                            <>
                                <IconCart className="h-4 w-4 shrink-0" />
                                <span>{p.has_options ? 'اختر النوع' : 'أضف للطلب'}</span>
                            </>
                        )}
                    </button>
                </div>
            </div>
        </article>
    );
}
