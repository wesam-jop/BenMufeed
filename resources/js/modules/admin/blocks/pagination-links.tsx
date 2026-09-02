import type { Paginated } from '@/types/admin';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

type PaginationLinksProps<T> = {
    paginator: Paginated<T>;
};

export default function PaginationLinks<T>({ paginator }: PaginationLinksProps<T>) {
    const { t } = useI18n();

    if (paginator.last_page <= 1) {
        return null;
    }

    const rangeLabel = t('common.showing_range')
        .replace(':from', String(paginator.from ?? 0))
        .replace(':to', String(paginator.to ?? 0))
        .replace(':total', String(paginator.total));

    return (
        <div className="flex flex-wrap items-center justify-between gap-4 pt-4">
            <p className="text-sm text-muted-foreground">{rangeLabel}</p>
            <div className="flex flex-wrap gap-1">
                {paginator.links.map((link, index) => {
                    if (!link.url) {
                        return (
                            <Button
                                key={index}
                                variant="outline"
                                size="sm"
                                disabled
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        );
                    }

                    return (
                        <Button
                            key={index}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            asChild
                        >
                            <Link href={link.url} preserveState preserveScroll>
                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                            </Link>
                        </Button>
                    );
                })}
            </div>
        </div>
    );
}
