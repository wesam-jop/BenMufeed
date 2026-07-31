import { Link } from '@inertiajs/react';
import CachedImage from '@/Components/Store/CachedImage';
import Icon from '@/Components/Ui/Icon';

/**
 * @param {{ id: number, name: string, slug: string, description?: string|null, image_url?: string|null, published_products_count?: number }} category
 */
export default function CategoryCard({ category }) {
    return (
        <Link href={`/categories/${category.slug}`} className="store-card-interactive group flex h-full flex-col">
            <div className="aspect-[5/3] w-full overflow-hidden bg-gradient-to-b from-store-cream to-store-primary/[0.05]">
                {category.image_url ? (
                    <CachedImage
                        src={category.image_url}
                        alt={category.name}
                        className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center text-sm text-store-muted">
                        لا صورة
                    </div>
                )}
            </div>
            <div className="flex flex-1 flex-col border-t border-store-border p-5">
                <h2 className="font-display text-h4 text-store-primary transition duration-fast group-hover:text-store-accent">
                    {category.name}
                </h2>
                {category.description ? (
                    <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-store-muted">{category.description}</p>
                ) : null}
                <p className="mt-auto flex items-center gap-1 pt-4 text-xs font-semibold text-store-secondary">
                    <span>{category.published_products_count ?? 0} طبق</span>
                    <Icon name="arrow-left" className="h-3.5 w-3.5 transition group-hover:-translate-x-0.5" />
                </p>
            </div>
        </Link>
    );
}
