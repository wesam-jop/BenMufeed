import { usePage } from '@inertiajs/react';
import SocialMeta from '@/Components/Store/SocialMeta';
import StoreBreadcrumbs from '@/Components/Store/StoreBreadcrumbs';
import StoreLayout from '@/Layouts/StoreLayout';
import { whatsappHref } from '@/lib/whatsapp';

export default function StaticPage({ seo, breadcrumbs, page }) {
    const { site } = usePage().props;
    const waHref = whatsappHref(site?.whatsapp_phone);

    return (
        <StoreLayout>
            <SocialMeta seo={seo} />
            <StoreBreadcrumbs items={breadcrumbs} />

            <article className="store-card-raised p-8 sm:p-10">
                <h1 className="font-display text-h1 text-store-primary">{page.heading}</h1>
                <p className="mt-6 max-w-3xl whitespace-pre-line text-body-lg leading-8 text-store-muted">{page.body}</p>
                {page.slug === 'contact' && waHref ? (
                    <a
                        href={waHref}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-8 inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-7 py-3 text-body font-bold text-white shadow-sm transition-all duration-fast hover:bg-emerald-500 hover:shadow-md active:scale-[0.98]"
                    >
                        راسلنا على واتساب
                    </a>
                ) : null}
            </article>
        </StoreLayout>
    );
}
