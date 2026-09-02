import { Link, usePage } from '@inertiajs/react';
import {
    Coffee,
    Facebook,
    Instagram,
    LayoutGrid,
    Menu,
    MessageCircle,
    ShoppingCart,
    User,
    X,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import LanguageSwitcher from '@/components/language-switcher';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useI18n } from '@/hooks/use-i18n';
import type { CartSummary } from '@/hooks/use-storefront-cart';
import type { Auth } from '@/types/auth';
import { cn } from '@/lib/utils';

export type StorefrontBusiness = {
    name: string;
    tagline?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
    logo?: string | null;
    currency?: string;
    whatsapp?: string | null;
    instagram?: string | null;
    facebook?: string | null;
    tiktok?: string | null;
};

export type NavCategory = {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    image_url?: string | null;
};

type StorefrontLayoutProps = {
    children: ReactNode;
    business: StorefrontBusiness;
    navCategories?: NavCategory[];
};

function SocialLink({
    href,
    label,
    children,
}: {
    href: string;
    label: string;
    children: ReactNode;
}) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={label}
            className="flex size-9 items-center justify-center rounded-full bg-amber-900/60 text-amber-100 transition-colors hover:bg-amber-800 hover:text-white"
        >
            {children}
        </a>
    );
}

export default function StorefrontLayout({
    children,
    business,
    navCategories = [],
}: StorefrontLayoutProps) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const { url, props } = usePage<{ auth?: Auth; cart?: CartSummary }>();
    const { auth, cart } = props;
    const { t, dir, locale } = useI18n();
    const cartCount = cart?.count ?? 0;
    const staffUser = auth?.user ?? null;
    const customer = auth?.customer ?? null;
    const accountUrl = staffUser
        ? (auth.dashboardUrl ?? '/admin')
        : customer
          ? '/customer'
          : '/auth/login';
    const accountLabel = staffUser
        ? t('nav.dashboard', 'لوحة التحكم')
        : customer
          ? t('nav.account', 'حسابي')
          : t('nav.login', 'دخول');
    const AccountIcon = staffUser ? LayoutGrid : User;

    const navLinks = [
        { href: '/', label: t('nav.home', 'الرئيسية'), match: (u: string) => u === '/' },
        {
            href: '/categories',
            label: t('nav.categories', 'الأقسام'),
            match: (u: string) => u.startsWith('/categories'),
        },
        {
            href: '/products',
            label: t('nav.products', 'المنتجات'),
            match: (u: string) => u.startsWith('/products'),
        },
        { href: '/about', label: t('nav.about', 'عن المقهى'), match: (u: string) => u === '/about' },
        {
            href: '/contact',
            label: t('nav.contact', 'تواصل معنا'),
            match: (u: string) => u === '/contact',
        },
    ];

    const whatsappUrl = business.whatsapp
        ? `https://wa.me/${business.whatsapp.replace(/\D/g, '')}`
        : null;

    return (
        <div dir={dir} lang={locale} className="min-h-screen bg-[#faf8f5] text-foreground">
            <header className="sticky top-0 z-50 border-b border-stone-200/80 bg-white/95 backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                    <Link href="/" className="flex shrink-0 items-center gap-2.5 font-bold text-stone-900">
                        {business.logo ? (
                            <img
                                src={business.logo}
                                alt={business.name}
                                className="size-9 rounded-lg object-cover ring-1 ring-stone-200"
                            />
                        ) : (
                            <span className="flex size-9 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                                <Coffee className="size-5" />
                            </span>
                        )}
                        <span className="hidden text-lg sm:inline">{business.name}</span>
                    </Link>

                    <nav className="hidden items-center gap-1 lg:flex">
                        {navLinks.map((link) => {
                            const active = link.match(url);

                            return (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className={cn(
                                        'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                        active
                                            ? 'bg-amber-50 text-amber-900'
                                            : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900',
                                    )}
                                >
                                    {link.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex items-center gap-1 sm:gap-2">
                        <LanguageSwitcher variant="ghost" />
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            onClick={() => setMobileOpen(true)}
                            aria-label={t('nav.menu', 'القائمة')}
                        >
                            <Menu className="size-5" />
                        </Button>
                        <Button variant="ghost" size="sm" asChild className="relative hidden sm:inline-flex">
                            <Link href="/cart">
                                <ShoppingCart className="size-4" />
                                <span className="hidden md:inline">{t('nav.cart', 'السلة')}</span>
                                {cartCount > 0 && (
                                    <Badge className="absolute -top-1.5 -start-1.5 size-5 justify-center rounded-full bg-amber-800 p-0 text-[10px]">
                                        {cartCount}
                                    </Badge>
                                )}
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild className="hidden md:inline-flex">
                            <Link href={accountUrl}>
                                <AccountIcon className="size-4" />
                                {accountLabel}
                            </Link>
                        </Button>
                    </div>
                </div>

                {mobileOpen && (
                    <>
                        <button
                            type="button"
                            className="fixed inset-0 z-40 bg-stone-900/40 backdrop-blur-sm lg:hidden"
                            aria-label="إغلاق القائمة"
                            onClick={() => setMobileOpen(false)}
                        />
                        <div className="fixed inset-y-0 start-0 z-50 w-[min(100%,20rem)] overflow-y-auto border-e bg-white shadow-xl lg:hidden">
                            <div className="flex items-center justify-between border-b px-4 py-4">
                                <span className="font-semibold text-stone-900">القائمة</span>
                                <Button variant="ghost" size="icon" onClick={() => setMobileOpen(false)}>
                                    <X className="size-5" />
                                </Button>
                            </div>
                            <div className="grid gap-1 p-3">
                                {navLinks.map((link) => {
                                    const active = link.match(url);

                                    return (
                                        <Link
                                            key={link.href}
                                            href={link.href}
                                            className={cn(
                                                'rounded-lg px-3 py-2.5 text-sm font-medium',
                                                active
                                                    ? 'bg-amber-50 text-amber-900'
                                                    : 'text-stone-700 hover:bg-stone-50',
                                            )}
                                            onClick={() => setMobileOpen(false)}
                                        >
                                            {link.label}
                                        </Link>
                                    );
                                })}
                                <Link
                                    href={accountUrl}
                                    className="rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 hover:bg-stone-50"
                                    onClick={() => setMobileOpen(false)}
                                >
                                    {accountLabel}
                                </Link>
                                <Link
                                    href="/cart"
                                    className="rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 hover:bg-stone-50"
                                    onClick={() => setMobileOpen(false)}
                                >
                                    {t('nav.cart', 'السلة')}
                                    {cartCount > 0 ? ` (${cartCount})` : ''}
                                </Link>
                                {navCategories.length > 0 && (
                                    <div className="mt-2 border-t pt-3">
                                        <p className="mb-2 px-3 text-xs font-medium uppercase tracking-wide text-stone-400">
                                            الأقسام
                                        </p>
                                        {navCategories.map((cat) => (
                                            <Link
                                                key={cat.id}
                                                href={`/categories/${cat.slug}`}
                                                className="block rounded-lg px-3 py-2 text-sm text-stone-600 hover:bg-stone-50"
                                                onClick={() => setMobileOpen(false)}
                                            >
                                                {cat.name}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </>
                )}
            </header>

            <main>{children}</main>

            <footer className="mt-20 border-t border-stone-800 bg-stone-950 text-stone-300">
                <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 md:grid-cols-4">
                    <div className="md:col-span-2">
                        <p className="text-xl font-bold text-white">{business.name}</p>
                        {business.tagline && (
                            <p className="mt-3 max-w-sm text-sm leading-relaxed text-stone-400">
                                {business.tagline}
                            </p>
                        )}
                        {(whatsappUrl || business.instagram || business.facebook) && (
                            <div className="mt-5 flex flex-wrap gap-2">
                                {whatsappUrl && (
                                    <SocialLink href={whatsappUrl} label="واتساب">
                                        <MessageCircle className="size-4" />
                                    </SocialLink>
                                )}
                                {business.instagram && (
                                    <SocialLink href={business.instagram} label="إنستغرام">
                                        <Instagram className="size-4" />
                                    </SocialLink>
                                )}
                                {business.facebook && (
                                    <SocialLink href={business.facebook} label="فيسبوك">
                                        <Facebook className="size-4" />
                                    </SocialLink>
                                )}
                            </div>
                        )}
                    </div>
                    <div>
                        <p className="mb-4 text-sm font-semibold uppercase tracking-wide text-stone-500">
                            روابط سريعة
                        </p>
                        <div className="grid gap-2 text-sm">
                            {navLinks.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="text-stone-400 transition-colors hover:text-white"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </div>
                    </div>
                    <div>
                        <p className="mb-4 text-sm font-semibold uppercase tracking-wide text-stone-500">
                            تواصل
                        </p>
                        <div className="flex flex-col gap-2 text-sm text-stone-400">
                            {business.phone && (
                                <a href={`tel:${business.phone}`} dir="ltr" className="hover:text-white">
                                    {business.phone}
                                </a>
                            )}
                            {business.email && (
                                <a href={`mailto:${business.email}`} dir="ltr" className="hover:text-white">
                                    {business.email}
                                </a>
                            )}
                            {business.address && <span>{business.address}</span>}
                        </div>
                    </div>
                </div>
                <div className="border-t border-stone-800">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-5 md:flex-row">
                        <p className="text-sm text-stone-500">
                            © {new Date().getFullYear()} {business.name}. جميع الحقوق محفوظة.
                        </p>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="text-stone-400 hover:bg-stone-900 hover:text-white"
                            asChild
                        >
                            <Link href={accountUrl}>
                                <AccountIcon className="size-4" />
                                {staffUser ? accountLabel : customer ? accountLabel : 'دخول الموظفين'}
                            </Link>
                        </Button>
                    </div>
                </div>
            </footer>
        </div>
    );
}
