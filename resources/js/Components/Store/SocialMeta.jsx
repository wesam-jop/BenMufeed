import { Head } from '@inertiajs/react';

/**
 * وسوم المشاركة والفهرسة — تُحدَّث أيضاً من السيرفر في HTML الأولي.
 *
 * @param {{ seo?: { title?: string, description?: string, image?: string|null, url?: string|null, type?: string, site_name?: string|null, robots?: string, keywords?: string|null, prev_url?: string|null, next_url?: string|null } }} props
 */
export default function SocialMeta({ seo }) {
    if (!seo?.title) {
        return null;
    }

    const title = seo.title;
    const description = seo.description || '';
    const image = seo.image || undefined;
    const url = seo.url || undefined;
    const type = seo.type || 'website';
    const siteName = seo.site_name || undefined;
    const robots = seo.robots || undefined;
    const keywords = seo.keywords || undefined;
    const prevUrl = seo.prev_url || undefined;
    const nextUrl = seo.next_url || undefined;

    return (
        <Head>
            <title>{title}</title>
            {description ? <meta name="description" content={description} /> : null}
            {robots ? <meta name="robots" content={robots} /> : null}
            {keywords ? <meta name="keywords" content={keywords} /> : null}

            <meta property="og:locale" content="ar_SA" />
            {siteName ? <meta property="og:site_name" content={siteName} /> : null}
            <meta property="og:type" content={type} />
            <meta property="og:title" content={title} />
            {description ? <meta property="og:description" content={description} /> : null}
            {url ? <meta property="og:url" content={url} /> : null}
            {url ? <link rel="canonical" href={url} /> : null}
            {prevUrl ? <link rel="prev" href={prevUrl} /> : null}
            {nextUrl ? <link rel="next" href={nextUrl} /> : null}
            {image ? <meta property="og:image" content={image} /> : null}
            {image ? <meta property="og:image:secure_url" content={image} /> : null}
            {image ? <meta property="og:image:alt" content={title} /> : null}

            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content={title} />
            {description ? <meta name="twitter:description" content={description} /> : null}
            {image ? <meta name="twitter:image" content={image} /> : null}
            {image ? <meta name="twitter:image:alt" content={title} /> : null}
        </Head>
    );
}
