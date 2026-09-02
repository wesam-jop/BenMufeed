import { Link } from '@inertiajs/react';
import { ArrowLeft, Coffee } from 'lucide-react';
import { useState } from 'react';
import { resolveMediaUrl } from '@/lib/media';
import { cn } from '@/lib/utils';

type Props = {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    imageUrl?: string | null;
    productsCount?: number;
    featured?: boolean;
    className?: string;
};

export default function CategoryCard({
    name,
    slug,
    image,
    imageUrl,
    productsCount = 0,
    featured = false,
    className,
}: Props) {
    const src = resolveMediaUrl(imageUrl, image);
    const [loaded, setLoaded] = useState(false);

    return (
        <Link
            href={`/categories/${slug}`}
            className={cn(
                'group flex flex-col overflow-hidden rounded-2xl bg-white/70 transition-all duration-500',
                'ring-1 ring-stone-900/[0.04] hover:bg-white hover:shadow-[0_20px_50px_-28px_rgba(28,25,23,0.35)]',
                featured && 'md:col-span-2',
                className,
            )}
        >
            <div
                className={cn(
                    'relative overflow-hidden bg-stone-100',
                    featured ? 'aspect-[21/10]' : 'aspect-[4/3]',
                )}
            >
                {!loaded && src && (
                    <div className="absolute inset-0 animate-pulse bg-stone-200/70" />
                )}

                {src ? (
                    <img
                        src={src}
                        alt={name}
                        loading="lazy"
                        onLoad={() => setLoaded(true)}
                        className={cn(
                            'size-full object-cover transition-transform duration-[1.1s] ease-out group-hover:scale-[1.04]',
                            loaded ? 'opacity-100' : 'opacity-0',
                        )}
                    />
                ) : (
                    <div className="flex size-full items-center justify-center bg-gradient-to-br from-stone-100 to-stone-200">
                        <Coffee className="size-10 text-stone-400" strokeWidth={1.25} />
                    </div>
                )}
            </div>

            <div className="flex flex-1 items-end justify-between gap-4 px-5 py-5 md:px-6 md:py-6">
                <div className="min-w-0">
                    <h2
                        className={cn(
                            'font-semibold tracking-tight text-stone-900 transition-colors group-hover:text-stone-700',
                            featured ? 'text-2xl md:text-3xl' : 'text-lg md:text-xl',
                        )}
                    >
                        {name}
                    </h2>
                    <p className="mt-1.5 text-sm text-stone-500">
                        {productsCount} منتج
                    </p>
                </div>
                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-600 transition-all duration-300 group-hover:bg-stone-900 group-hover:text-white">
                    <ArrowLeft className="size-4 transition-transform duration-300 group-hover:-translate-x-0.5" />
                </span>
            </div>
        </Link>
    );
}
