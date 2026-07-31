import { IconFacebook, IconInstagram, IconTikTok, IconWhatsApp } from '@/Components/Store/icons/SocialIcons';

function SocialIconButton({ href, label, className, children }) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noreferrer noopener"
            role="listitem"
            aria-label={label}
            className={className}
        >
            {children}
            <span className="sr-only">{label}</span>
        </a>
    );
}

const sharedBtn =
    'group relative flex items-center justify-center rounded-full border border-zinc-500/40 bg-zinc-800/60 text-zinc-200 shadow-md backdrop-blur-sm transition duration-300 ease-out hover:scale-110 hover:shadow-xl active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-store-secondary focus-visible:ring-offset-2 focus-visible:ring-offset-store-primary';

/**
 * @param {{ facebookUrl?: string|null, instagramUrl?: string|null, tiktokUrl?: string|null, whatsappHref?: string|null, className?: string, size?: 'md'|'sm' }} props
 */
export default function StoreSocialLinks({
    facebookUrl,
    instagramUrl,
    tiktokUrl,
    whatsappHref,
    className = '',
    size = 'md',
}) {
    const dim = size === 'sm' ? 'h-10 w-10' : 'h-12 w-12';
    const iconClass = `${size === 'sm' ? 'h-5 w-5' : 'h-6 w-6'} transition-transform duration-300 group-hover:scale-110`;

    if (!facebookUrl && !instagramUrl && !tiktokUrl && !whatsappHref) {
        return null;
    }

    return (
        <div className={`flex flex-wrap items-center justify-center gap-3 sm:justify-end ${className}`} role="list" aria-label="تابعنا">
            {facebookUrl ? (
                <SocialIconButton
                    href={facebookUrl}
                    label="Facebook"
                    className={`${sharedBtn} ${dim} hover:border-[#1877F2] hover:bg-[#1877F2] hover:text-white`}
                >
                    <IconFacebook className={iconClass} />
                </SocialIconButton>
            ) : null}
            {instagramUrl ? (
                <SocialIconButton
                    href={instagramUrl}
                    label="Instagram"
                    className={`${sharedBtn} ${dim} hover:border-pink-500 hover:bg-gradient-to-br hover:from-[#f9ce34] hover:via-[#ee2a7b] hover:to-[#6228d7] hover:text-white`}
                >
                    <IconInstagram className={iconClass} />
                </SocialIconButton>
            ) : null}
            {tiktokUrl ? (
                <SocialIconButton
                    href={tiktokUrl}
                    label="TikTok"
                    className={`${sharedBtn} ${dim} hover:border-zinc-300 hover:bg-black hover:text-white`}
                >
                    <IconTikTok className={iconClass} />
                </SocialIconButton>
            ) : null}
            {whatsappHref ? (
                <SocialIconButton
                    href={whatsappHref}
                    label="واتساب"
                    className={`${sharedBtn} ${dim} hover:border-emerald-400 hover:bg-emerald-500 hover:text-white`}
                >
                    <IconWhatsApp className={iconClass} />
                </SocialIconButton>
            ) : null}
        </div>
    );
}
