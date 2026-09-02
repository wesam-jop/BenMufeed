import { Head } from '@inertiajs/react';
import StorefrontPageRenderer from '@/components/storefront/page-renderer/StorefrontPageRenderer';
import StorefrontLayout, {
    type NavCategory,
    type StorefrontBusiness,
} from '@/layouts/storefront-layout';
import type { CmsPageTheme, CmsSection } from '@/types/cms-page';

type Category = {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    image_url?: string | null;
    products_count?: number;
};

type Product = {
    id: number;
    name: string;
    sale_price: number | string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    category?: { id: number; name: string; slug?: string } | null;
};

type Props = {
    business: StorefrontBusiness & Record<string, unknown>;
    branch?: { id: number; name: string; code: string } | null;
    navCategories?: NavCategory[];
    categories?: Category[];
    featuredProducts?: Product[];
    cmsPage: {
        id: number;
        title: string;
        path: string;
        seo_title?: string | null;
        seo_description?: string | null;
        theme: CmsPageTheme;
        sections: CmsSection[];
    };
};

export default function StorefrontCmsPage({
    business,
    navCategories = [],
    categories = [],
    featuredProducts = [],
    cmsPage,
}: Props) {
    const title = cmsPage.seo_title || cmsPage.title || business.name;
    const description =
        cmsPage.seo_description ||
        (typeof business.meta_description === 'string' ? business.meta_description : undefined);

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={title}>
                {description ? <meta head-key="description" name="description" content={description} /> : null}
            </Head>
            <div className="mx-auto max-w-6xl px-4 py-8 md:px-6">
                <StorefrontPageRenderer
                    theme={cmsPage.theme}
                    sections={cmsPage.sections}
                    business={business}
                    categories={categories}
                    featuredProducts={featuredProducts}
                />
            </div>
        </StorefrontLayout>
    );
}
