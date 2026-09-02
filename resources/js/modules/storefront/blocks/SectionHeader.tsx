import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    subtitle?: string;
    href?: string;
    linkLabel?: string;
    className?: string;
    centered?: boolean;
};

export default function SectionHeader({
    title,
    subtitle,
    href,
    linkLabel = 'عرض الكل',
    className,
    centered = false,
}: Props) {
    return (
        <div
            className={cn(
                'mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between',
                centered && 'items-center text-center sm:items-center sm:justify-center',
                className,
            )}
        >
            <div className={cn(centered && 'max-w-2xl')}>
                <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">{title}</h2>
                {subtitle && (
                    <p className="mt-2 text-base text-stone-500 leading-relaxed">{subtitle}</p>
                )}
            </div>
            {href && !centered && (
                <Button
                    variant="outline"
                    className="shrink-0 border-stone-200 text-stone-700 hover:bg-stone-50"
                    asChild
                >
                    <Link href={href}>
                        {linkLabel}
                        <ArrowLeft className="size-4" />
                    </Link>
                </Button>
            )}
        </div>
    );
}
