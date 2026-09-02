import { router, usePage } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    className?: string;
    variant?: 'default' | 'ghost' | 'outline';
    size?: 'default' | 'sm' | 'icon';
};

export default function LanguageSwitcher({
    className,
    variant = 'outline',
    size = 'sm',
}: Props) {
    const { locale } = usePage().props as { locale?: string };
    const current = locale === 'en' ? 'en' : 'ar';
    const next = current === 'ar' ? 'en' : 'ar';
    const label = current === 'ar' ? 'EN' : 'ع';

    return (
        <Button
            type="button"
            variant={variant}
            size={size}
            className={cn('gap-1.5 font-semibold', className)}
            onClick={() => router.post(`/locale/${next}`, {}, { preserveScroll: true })}
            title={current === 'ar' ? 'Switch to English' : 'التبديل إلى العربية'}
        >
            <Languages className="size-3.5" />
            {label}
        </Button>
    );
}
