import Icon from '@/Components/Ui/Icon';
import EmptyState from '@/Components/Ui/EmptyState';
import { Link } from '@inertiajs/react';
import CachedImage from '@/Components/Store/CachedImage';
import JsonLd from '@/Components/Store/JsonLd';
import SocialMeta from '@/Components/Store/SocialMeta';
import { useEffect, useMemo } from 'react';
import HomeHeroSlider from '@/Components/Store/HomeHeroSlider';
import ProductCard from '@/Components/Store/ProductCard';
import StoreLayout from '@/Layouts/StoreLayout';

const categoryCardClass =
    'store-card-interactive group flex w-[min(260px,82vw)] shrink-0 flex-col';

export default function Home({ seo, structuredData, site, heroSlides = [], categories = [], spotlightProducts = [] }) {
    const marqueeCategories = useMemo(() => {
        if (!categories.length) {
            return [];
        }

        return [...categories, ...categories];
    }, [categories]);

    const marqueeDurationSec = useMemo(() => {
        const n = categories.length;

        return Math.max(18, Math.min(60, n * 6));
    }, [categories.length]);

    useEffect(() => {
        if (window.location.hash === '#categories') {
            window.requestAnimationFrame(() => {
                document.getElementById('categories')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }, []);

    return (
        <StoreLayout>
            <SocialMeta seo={seo} />
            <JsonLd structuredData={structuredData} />

            {heroSlides.length > 0 ? (
                <HomeHeroSlider heroSlides={heroSlides} />
            ) : (
                <section className="relative overflow-hidden rounded-3xl border border-store-border bg-restaurant-hero text-store-surface shadow-lg">
                    {site.hero_banner_url ? (
                        <img
                            src={site.hero_banner_url}
                            alt=""
                            className="absolute inset-0 h-full w-full object-cover opacity-30"
                            loading="eager"
                            fetchPriority="high"
                            decoding="sync"
                        />
                    ) : null}
                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-bl from-store-primary/95 via-store-primary/70 to-transparent" />
                    <div className="relative px-6 py-16 sm:px-12 sm:py-20">
                        {site.site_name ? (
                            <p className="store-section-eyebrow text-store-secondary">{site.site_name}</p>
                        ) : null}
                        {site.hero_title ? (
                            <h1 className="font-display mt-3 text-h1 leading-tight sm:text-display">
                                {site.hero_title}
                            </h1>
                        ) : null}
                        {site.hero_subtitle ? (
                            <p className="mt-4 max-w-xl text-body-lg leading-relaxed text-store-surface/85">
                                {site.hero_subtitle}
                            </p>
                        ) : null}
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href="#categories" className="store-btn-primary">
                                استكشف القائمة
                            </Link>
                            <Link href="/products" className="store-btn-outline border-store-surface/25 text-store-surface hover:border-store-surface/40 hover:bg-store-surface/10">
                                كل المنتجات
                            </Link>
                        </div>
                    </div>
                </section>
            )}

            <section id="categories" className="mt-14 scroll-mt-28 sm:mt-16">
                <div className="text-center">
                    <p className="store-section-eyebrow">القائمة</p>
                    <h2 className="store-section-title mt-2">الأقسام</h2>
                    <p className="store-section-sub mx-auto">اختر القسم المناسب لك</p>
                </div>

                <div className="mt-8 flex flex-wrap items-center justify-center gap-3 sm:justify-between">
                    <span className="text-body font-medium text-store-muted">تصفّح حسب القسم</span>
                    <Link href="/categories" className="store-btn-primary px-5 py-2 text-caption sm:text-body">
                        كل الأقسام
                    </Link>
                </div>

                {categories.length === 0 ? (
                    <EmptyState
                        className="mt-10"
                        title="لا توجد أقسام بعد"
                        description="سيتم إضافة أقسام القائمة قريباً. تابعنا للاطلاع على آخر المستجدات."
                        actionLabel="تصفح القائمة"
                        actionHref="/products"
                    />
                ) : (
                    <div className="relative mt-8">
                        <div
                            className="pointer-events-none absolute inset-y-0 start-0 z-10 w-10 bg-gradient-to-e from-store-cream to-transparent sm:w-14"
                            aria-hidden
                        />
                        <div
                            className="pointer-events-none absolute inset-y-0 end-0 z-10 w-10 bg-gradient-to-s from-store-cream to-transparent sm:w-14"
                            aria-hidden
                        />
                        <div
                            className="overflow-hidden rounded-2xl border border-store-border bg-store-surface/90 py-4 shadow-inner motion-reduce:overflow-x-auto"
                            dir="ltr"
                        >
                            <div
                                className="flex w-max gap-4 px-4 motion-safe:animate-category-marquee motion-reduce:animate-none hover:[animation-play-state:paused] sm:gap-5"
                                style={{
                                    '--category-marquee-duration': `${marqueeDurationSec}s`,
                                }}
                            >
                                {marqueeCategories.map((c, i) => (
                                    <Link key={`${c.id}-${i}`} href={`/categories/${c.slug}`} className={categoryCardClass}>
                                        <div className="aspect-[4/3] w-full overflow-hidden bg-neutral-50">
                                            {c.image_url ? (
                                                <CachedImage
                                                    src={c.image_url}
                                                    alt={c.name}
                                                    className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex h-full w-full items-center justify-center text-caption text-store-muted">
                                                    لا صورة
                                                </div>
                                            )}
                                        </div>
                                        <div className="border-t border-store-border p-4 text-end" dir="rtl">
                                            <h3 className="font-display text-h5 text-store-primary">{c.name}</h3>
                                            <p className="mt-1 text-caption text-store-muted">
                                                {c.published_products_count} منتج
                                            </p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </section>

            {spotlightProducts.length > 0 ? (
                <section className="mt-14 sm:mt-16">
                    <div className="flex flex-wrap items-end justify-between gap-4 border-b border-store-border pb-5">
                        <div>
                            <p className="store-section-eyebrow">مختاراتنا</p>
                            <h2 className="store-section-title mt-1 text-h3 sm:text-h2">منتجات مميزة</h2>
                        </div>
                        <Link
                            href="/products"
                            className="inline-flex items-center gap-1 text-body font-semibold text-store-accent transition duration-fast hover:text-store-secondary"
                        >
                            القائمة كاملة
                            <Icon name="arrow-left" className="h-4 w-4" />
                        </Link>
                    </div>
                    <div className="mt-8 grid grid-cols-2 items-stretch gap-3 sm:gap-5 lg:grid-cols-4">
                        {spotlightProducts.map((p) => (
                            <ProductCard key={p.id} product={p} />
                        ))}
                    </div>
                </section>
            ) : null}
        </StoreLayout>
    );
}
