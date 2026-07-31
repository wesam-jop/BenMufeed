import { Link, usePage } from '@inertiajs/react';
import SiteLogo from '@/Components/Store/SiteLogo';
import StoreBottomNav from '@/Components/Store/StoreBottomNav';
import StoreFloatingCart from '@/Components/Store/StoreFloatingCart';
import StoreHeaderNav from '@/Components/Store/StoreHeaderNav';
import StoreSocialLinks from '@/Components/Store/StoreSocialLinks';
import TableOrderingBanner from '@/Components/Store/TableOrderingBanner';
import { TableOrderingProvider } from '@/Components/Store/TableOrderingProvider';
import { whatsappHref } from '@/lib/whatsapp';

/**
 * Wraps store page content so context hooks (e.g. useTableOrdering) run inside providers.
 */
export function withStoreLayout(PageComponent) {
    function StorePage(props) {
        return (
            <StoreLayout>
                <PageComponent {...props} />
            </StoreLayout>
        );
    }

    StorePage.displayName = `WithStoreLayout(${PageComponent.displayName || PageComponent.name || 'Page'})`;

    return StorePage;
}

export default function StoreLayout({ children }) {
    const { site } = usePage().props;
    const waHref = whatsappHref(site?.whatsapp_phone);
    const siteName = site?.site_name || 'بن مفيد';
    const tagline =
        site?.hero_subtitle ||
        (site?.site_description && site.site_description.length < 120 ? site.site_description : null) ||
        'كافيتيريا · مشروبات · أراكيل · مباريات';

    return (
        <TableOrderingProvider>
            <div className="store-shell">
                <a
                    href="#main-content"
                    className="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-store-secondary focus:px-4 focus:py-2 focus:text-body focus:font-bold focus:text-store-primary focus:shadow-md"
                >
                    تخطي إلى المحتوى
                </a>

                <div className="store-top-bar">
                    <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-4 gap-y-1 px-4 py-2 text-center text-2xs sm:justify-between sm:text-caption">
                        <span className="inline-flex items-center gap-2 font-medium">
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/20 bg-emerald-500/10 px-2.5 py-0.5 text-emerald-300">
                                <span
                                    className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"
                                    aria-hidden
                                />
                                مفتوح الآن
                            </span>
                            <span className="text-store-surface/80">
                                مرحباً في <span className="font-semibold text-white">{siteName}</span>
                            </span>
                        </span>
                        <span className="text-store-surface/60">
                            الأسعار ({site?.currency_symbol || '$'})
                            {waHref ? (
                                <>
                                    <span className="mx-2 text-store-surface/25" aria-hidden>
                                        ·
                                    </span>
                                    <a
                                        href={waHref}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="font-semibold text-store-secondary transition duration-fast hover:text-white"
                                    >
                                        احجز طاولتك
                                    </a>
                                </>
                            ) : null}
                        </span>
                    </div>
                </div>

                <header className="store-header">
                    <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-6">
                            <div className="flex flex-col items-center gap-1.5 lg:items-start">
                                <SiteLogo variant="header" />
                                <p className="hidden max-w-xs text-center text-caption leading-relaxed text-store-muted lg:block lg:text-start">
                                    {tagline}
                                </p>
                            </div>
                            <StoreHeaderNav />
                        </div>
                        <p className="mt-2 text-center text-caption text-store-muted lg:hidden">{tagline}</p>
                    </div>
                </header>

                <main
                    id="main-content"
                    className="mx-auto max-w-7xl px-4 py-6 pb-[calc(6rem+env(safe-area-inset-bottom))] sm:px-6 sm:py-8 lg:px-8"
                >
                    <TableOrderingBanner />
                    {children}
                </main>

                <footer className="relative overflow-hidden border-t border-store-border bg-store-primary pb-[calc(6.5rem+env(safe-area-inset-bottom))] text-store-surface/85 sm:pb-12">
                    <div
                        className="pointer-events-none absolute inset-0 bg-restaurant-gold opacity-[0.03]"
                        aria-hidden
                    />
                    <div className="relative mx-auto flex max-w-7xl flex-col gap-10 px-4 py-14 sm:flex-row sm:items-start sm:justify-between sm:px-6 lg:px-8">
                        <div className="flex flex-col items-center gap-5 sm:items-start">
                            <SiteLogo
                                variant="footer"
                                asLink={false}
                                className="h-16 w-16 rounded-2xl ring-1 ring-store-secondary/30"
                            />
                            <div className="text-center sm:text-start">
                                <p className="font-display text-h2 font-bold text-white">{siteName}</p>
                                <p className="mt-2 max-w-sm text-body leading-relaxed text-store-surface/55">
                                    {site?.site_description ||
                                        'مشروبات ساخنة وباردة، أراكيل بنكهات مميزة، ومشاهدة المباريات في أجواء مريحة.'}
                                </p>
                            </div>
                        </div>
                        <StoreSocialLinks
                            facebookUrl={site?.facebook_url}
                            instagramUrl={site?.instagram_url}
                            tiktokUrl={site?.tiktok_url}
                            whatsappHref={waHref}
                        />
                    </div>
                    <nav className="relative mx-auto flex max-w-7xl flex-wrap justify-center gap-x-6 gap-y-2 px-4 pb-6 text-body font-medium">
                        {[
                            ['/about', 'من نحن'],
                            ['/shipping', 'ساعات العمل'],
                            ['/contact', 'اتصل بنا'],
                            ['/products', 'القائمة'],
                            ['/categories', 'الأقسام'],
                        ].map(([href, label]) => (
                            <Link
                                key={href}
                                href={href}
                                className="text-store-surface/50 transition duration-fast hover:text-store-secondary"
                            >
                                {label}
                            </Link>
                        ))}
                    </nav>
                    <div className="relative border-t border-white/[0.06] py-5 text-center text-caption text-store-surface/35">
                        © {new Date().getFullYear()} {siteName}
                    </div>
                </footer>

                <div aria-live="polite" aria-atomic="true" className="sr-only" id="cart-live-region" />

                <StoreFloatingCart />
                <StoreBottomNav />
            </div>
        </TableOrderingProvider>
    );
}
