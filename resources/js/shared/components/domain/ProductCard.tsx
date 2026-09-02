import { Coffee, Heart, ShoppingBag } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/format';
import { resolveMediaUrl } from '@/lib/media';
import { cn } from '@/lib/utils';

type ProductCardProps = {
    id: number;
    name: string;
    price: number;
    image?: string | null;
    imageUrl?: string | null;
    category?: string;
    description?: string | null;
    currency?: string;
    onAdd?: (id: number) => void;
    addLabel?: string;
    isFavorite?: boolean;
    onToggleFavorite?: (id: number) => void;
    showFavorite?: boolean;
    adding?: boolean;
};

export default function ProductCard({
    id,
    name,
    price,
    image,
    imageUrl,
    category,
    description,
    currency = 'SAR',
    onAdd,
    addLabel = 'أضف للسلة',
    isFavorite = false,
    onToggleFavorite,
    showFavorite = false,
    adding = false,
}: ProductCardProps) {
    const src = resolveMediaUrl(imageUrl, image);
    const [imageLoaded, setImageLoaded] = useState(false);
    const [imageFailed, setImageFailed] = useState(false);
    const showImage = Boolean(src) && !imageFailed;

    return (
        <article className="group relative flex h-full flex-col overflow-hidden rounded-[1.5rem] bg-white shadow-sm ring-1 ring-stone-200/70 transition-all duration-400 hover:-translate-y-1 hover:shadow-2xl hover:ring-amber-300/40">
            <div className="relative aspect-[4/5] overflow-hidden bg-gradient-to-br from-stone-100 via-amber-50 to-stone-200">
                {showImage && !imageLoaded && (
                    <div className="absolute inset-0 animate-pulse bg-gradient-to-br from-stone-200 to-amber-100" />
                )}

                {showImage ? (
                    <img
                        src={src!}
                        alt={name}
                        loading="lazy"
                        onLoad={() => setImageLoaded(true)}
                        onError={() => setImageFailed(true)}
                        className={cn(
                            'size-full object-cover transition-all duration-700 ease-out group-hover:scale-110',
                            imageLoaded ? 'opacity-100' : 'opacity-0',
                        )}
                    />
                ) : (
                    <div className="flex size-full flex-col items-center justify-center gap-2 bg-gradient-to-br from-amber-800 via-stone-800 to-stone-950 text-white">
                        <Coffee className="size-14 opacity-90" strokeWidth={1.5} />
                        <span className="text-xs text-white/50">بدون صورة</span>
                    </div>
                )}

                <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-stone-950/50 via-transparent to-transparent opacity-80" />

                {category && (
                    <Badge className="absolute start-3 top-3 border-0 bg-white/95 px-2.5 py-1 text-xs font-medium text-stone-800 shadow-sm backdrop-blur-sm">
                        {category}
                    </Badge>
                )}

                {showFavorite && onToggleFavorite && (
                    <Button
                        type="button"
                        size="icon"
                        variant="secondary"
                        className={cn(
                            'absolute end-3 top-3 size-9 rounded-full border-0 bg-white/95 shadow-sm backdrop-blur-sm hover:bg-white',
                            isFavorite && 'text-rose-500 hover:text-rose-600',
                        )}
                        onClick={(e) => {
                            e.preventDefault();
                            onToggleFavorite(id);
                        }}
                        aria-label={isFavorite ? 'إزالة من المفضلة' : 'إضافة إلى المفضلة'}
                    >
                        <Heart className={cn('size-4', isFavorite && 'fill-current')} />
                    </Button>
                )}

                <div className="absolute inset-x-0 bottom-0 p-3 sm:hidden">
                    <p className="truncate text-sm font-semibold text-white drop-shadow">{name}</p>
                </div>
            </div>

            <div className="flex flex-1 flex-col gap-2 p-4">
                <h3 className="line-clamp-2 text-base font-semibold leading-snug text-stone-900">
                    {name}
                </h3>
                {description && (
                    <p className="line-clamp-2 text-sm leading-relaxed text-stone-500">{description}</p>
                )}

                <div className="mt-auto flex items-end justify-between gap-2 pt-2">
                    <div>
                        <p className="text-[11px] font-medium uppercase tracking-wide text-stone-400">السعر</p>
                        <p className="text-lg font-bold text-amber-900">
                            {formatCurrency(price, currency)}
                        </p>
                    </div>
                </div>

                {onAdd && (
                    <Button
                        type="button"
                        className="mt-2 h-11 w-full bg-stone-900 text-sm font-semibold text-white shadow-sm hover:bg-stone-800"
                        disabled={adding}
                        onClick={() => onAdd(id)}
                    >
                        {adding ? (
                            'جاري الإضافة...'
                        ) : (
                            <>
                                <ShoppingBag className="size-4" />
                                {addLabel}
                            </>
                        )}
                    </Button>
                )}
            </div>
        </article>
    );
}
