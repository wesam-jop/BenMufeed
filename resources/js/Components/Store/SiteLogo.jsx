import { Link, usePage } from '@inertiajs/react';
import CachedImage from '@/Components/Store/CachedImage';

const variants = {
    header: 'h-[100px] w-auto max-w-[220px] object-contain',
    footer: 'h-12 w-auto max-w-[160px] object-contain brightness-0 invert',
    compact: 'h-10 w-auto max-w-[120px] object-contain',
};

const fallbackVariants = {
    header: 'bg-store-primary/8 text-store-primary ring-store-secondary/30 text-lg sm:text-2xl',
    footer: 'bg-white/10 text-white ring-white/25 text-sm',
    compact: 'bg-store-primary/8 text-store-primary ring-store-secondary/30 text-sm',
};

/**
 * شعار المطعم من إعدادات الموقع (site.logo_url) — بدون صورة افتراضية ثابتة.
 *
 * @param {{ variant?: 'header'|'footer'|'compact', href?: string|null, className?: string, asLink?: boolean }} props
 */
export default function SiteLogo({ variant = 'header', href = '/', className = '', asLink = true }) {
    const { site } = usePage().props;
    const name = site?.site_name || 'بن مفيد';
    const imgClass = `${variants[variant] || variants.header} ${className}`.trim();

    const content = site?.logo_url ? (
        <CachedImage src={site.logo_url} alt={name} className={imgClass} />
    ) : (
        <span
            className={`font-display inline-flex items-center justify-center rounded-xl px-4 py-2 text-center font-semibold ring-1 ${fallbackVariants[variant] || fallbackVariants.header}`}
        >
            {name}
        </span>
    );

    if (!asLink) {
        return content;
    }

    return (
        <Link
            href={href}
            className="inline-flex focus:outline-none focus-visible:ring-2 focus-visible:ring-store-secondary/80 focus-visible:ring-offset-2"
        >
            {content}
        </Link>
    );
}
