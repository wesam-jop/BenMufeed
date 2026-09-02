import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

type PageHeaderProps = {
    title: string;
    description?: string;
    createHref?: string;
    createLabel?: string;
    actions?: ReactNode;
};

export default function PageHeader({
    title,
    description,
    createHref,
    createLabel,
    actions,
}: PageHeaderProps) {
    const { t } = useI18n();
    const label = createLabel ?? t('common.add_new');

    return (
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                )}
            </div>
            <div className="flex items-center gap-2">
                {actions}
                {createHref && (
                    <Button asChild>
                        <Link href={createHref}>
                            <Plus className="size-4" />
                            {label}
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}
