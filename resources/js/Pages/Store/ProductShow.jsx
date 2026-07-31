import { Link, usePage } from '@inertiajs/react';
import CachedImage from '@/Components/Store/CachedImage';
import JsonLd from '@/Components/Store/JsonLd';
import ProductOptionsPicker from '@/Components/Store/ProductOptionsPicker';
import SocialMeta from '@/Components/Store/SocialMeta';
import StoreBreadcrumbs from '@/Components/Store/StoreBreadcrumbs';
import { useMemo, useRef, useState } from 'react';
import ProductCard from '@/Components/Store/ProductCard';
import ProductRichContent from '@/Components/Store/ProductRichContent';
import StoreLayout from '@/Layouts/StoreLayout';
import { upsertCartItem } from '@/lib/cart';
import { formatPrice } from '@/lib/formatPrice';

export default function ProductShow({ seo, structuredData, breadcrumbs, product, similarProducts }) {
    const { site } = usePage().props;
    const symbol = site?.currency_symbol || '$';
    const [active, setActive] = useState(0);
    const [addedFlash, setAddedFlash] = useState(false);
    const hideAdded = useRef(0);
    const images = product.images?.length ? product.images : [];
    const hasOptions = product.has_options || (product.variants?.length ?? 0) > 0 || (product.addons?.length ?? 0) > 0;

    const addConfiguredItem = (item, quantity) => {
        upsertCartItem(item, quantity);
        setAddedFlash(true);
        window.clearTimeout(hideAdded.current);
        hideAdded.current = window.setTimeout(() => setAddedFlash(false), 2500);
    };

    const mainImage = useMemo(() => images[active] || product.image_url, [images, active, product.image_url]);

    return (
        <StoreLayout>
            <SocialMeta seo={seo} />
            <JsonLd structuredData={structuredData} />
            <StoreBreadcrumbs items={breadcrumbs} />

            <div className="grid gap-10 lg:grid-cols-2">
                <div>
                    <div className="store-card overflow-hidden rounded-3xl">
                        {mainImage ? (
                            <CachedImage src={mainImage} alt={product.name} className="aspect-square w-full object-cover" />
                        ) : (
                            <div className="flex aspect-square w-full items-center justify-center bg-store-primary/[0.06] text-store-primary/45">
                                لا صورة
                            </div>
                        )}
                    </div>
                    {images.length > 1 ? (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {images.map((src, idx) => (
                                <button
                                    type="button"
                                    key={src + idx}
                                    onClick={() => setActive(idx)}
                                    aria-label={`صورة ${idx + 1}`}
                                    aria-current={idx === active ? 'true' : undefined}
                                    className={`h-16 w-16 overflow-hidden rounded-lg border-2 bg-white ${
                                        idx === active ? 'border-store-secondary' : 'border-store-primary/15'
                                    }`}
                                >
                                    <CachedImage src={src} alt="" className="h-full w-full object-cover" />
                                </button>
                            ))}
                        </div>
                    ) : null}
                </div>
                <div>
                    {product.category ? (
                        <Link
                            href={`/categories/${product.category.slug}`}
                            className="text-sm font-semibold text-store-secondary transition hover:brightness-110"
                        >
                            {product.category.name}
                        </Link>
                    ) : null}
                    <h1 className="font-display mt-2 text-3xl font-bold text-store-primary">{product.name}</h1>
                    <div className="mt-4 flex items-baseline gap-3">
                        <span className="text-3xl font-bold text-store-secondary">
                            {hasOptions ? `من ${formatPrice(product.price, symbol)}` : formatPrice(product.price, symbol)}
                        </span>
                        {product.compare_at_price ? (
                            <span className="text-lg text-store-primary/45 line-through">
                                {formatPrice(product.compare_at_price, symbol)}
                            </span>
                        ) : null}
                    </div>
                    {product.short_description ? (
                        <p className="mt-6 text-store-primary/70">{product.short_description}</p>
                    ) : null}
                    {product.is_delivery_available === false ? (
                        <div className="mt-4 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            هذا الصنف غير متاح للتوصيل، ويمكن طلبه فقط بيع سفري بدون طاولة أو من الطاولة.
                        </div>
                    ) : null}

                    {hasOptions ? (
                        <div className="mt-8 rounded-2xl border border-store-primary/10 bg-store-surface p-5 shadow-sm">
                            <ProductOptionsPicker
                                product={product}
                                symbol={symbol}
                                onConfirm={addConfiguredItem}
                            />
                        </div>
                    ) : (
                        <>
                            <div className="mt-6 flex items-center gap-3">
                                <label htmlFor="product-qty" className="text-sm font-medium text-store-primary">
                                    الكمية
                                </label>
                                <input
                                    id="product-qty"
                                    type="number"
                                    min={1}
                                    max={999}
                                    defaultValue={1}
                                    className="w-24 rounded-lg border border-store-primary/15 bg-store-surface px-3 py-2 text-sm"
                                    onChange={(e) => {
                                        e.currentTarget.dataset.qty = String(
                                            Math.min(999, Math.max(1, Number(e.target.value) || 1)),
                                        );
                                    }}
                                />
                            </div>
                            <div className="mt-8 flex flex-col gap-2 sm:flex-row sm:items-center">
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        const qtyInput = document.getElementById('product-qty');
                                        const qty = Math.min(
                                            999,
                                            Math.max(1, Number(qtyInput?.dataset.qty || qtyInput?.value || 1)),
                                        );
                                        addConfiguredItem(
                                            {
                                                product_id: product.id,
                                                slug: product.slug,
                                                name: product.name,
                                                price: product.price,
                                                compare_at_price: product.compare_at_price,
                                                image_url: product.image_url,
                                            },
                                            qty,
                                        );
                                    }}
                                    className="store-btn-primary w-full px-8 py-4 sm:w-auto"
                                >
                                    أضف إلى طلبك
                                </button>
                                {addedFlash ? (
                                    <span className="text-sm font-medium text-store-accent sm:ms-3">
                                        تمت الإضافة — يمكنك متابعة اختيار الأطباق.
                                    </span>
                                ) : null}
                            </div>
                        </>
                    )}

                    {hasOptions && addedFlash ? (
                        <p className="mt-4 text-sm font-medium text-store-accent">تمت الإضافة — يمكنك متابعة اختيار الأطباق.</p>
                    ) : null}
                </div>
            </div>

            {product.description ? (
                <section className="mt-12 border-t border-store-primary/10 pt-10 lg:mt-14">
                    <h2 className="font-display text-xl font-bold text-store-primary">تفاصيل الطبق</h2>
                    <ProductRichContent html={product.description} className="mt-5" />
                </section>
            ) : null}

            {similarProducts.length ? (
                <section className="mt-16 border-t border-store-primary/10 pt-12">
                    <h2 className="font-display text-xl font-bold text-store-primary">أطباق قد تعجبك</h2>
                    <div className="mt-6 grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {similarProducts.map((p) => (
                            <ProductCard key={p.id} product={p} />
                        ))}
                    </div>
                </section>
            ) : null}
        </StoreLayout>
    );
}
