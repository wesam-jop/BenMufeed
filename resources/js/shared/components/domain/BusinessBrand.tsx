import { usePage } from '@inertiajs/react';
import { Coffee } from 'lucide-react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

export type SharedBusiness = {
    name: string;
    logo?: string | null;
    tagline?: string | null;
};

type BusinessBrandProps = {
    subtitle?: string | null;
    iconWrapperClassName?: string;
    textClassName?: string;
    fallbackIcon?: ReactNode;
};

export function useSharedBusiness(): SharedBusiness {
    const { business, name } = usePage<{ business?: SharedBusiness; name: string }>().props;

    return {
        name: business?.name ?? name,
        logo: business?.logo ?? null,
        tagline: business?.tagline ?? null,
    };
}

export default function BusinessBrand({
    subtitle,
    iconWrapperClassName,
    textClassName,
    fallbackIcon,
}: BusinessBrandProps) {
    const business = useSharedBusiness();
    const displaySubtitle = subtitle;

    return (
        <>
            <div
                className={cn(
                    'flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-md',
                    iconWrapperClassName,
                )}
            >
                {business.logo ? (
                    <img
                        src={business.logo}
                        alt={business.name}
                        className="size-full object-cover"
                    />
                ) : (
                    fallbackIcon ?? (
                        <Coffee className="size-4" />
                    )
                )}
            </div>
            <div className={cn('grid min-w-0 flex-1 text-sm leading-tight', textClassName)}>
                <span className="truncate font-semibold">{business.name}</span>
                {displaySubtitle ? (
                    <span className="truncate text-xs text-muted-foreground">{displaySubtitle}</span>
                ) : null}
            </div>
        </>
    );
}
