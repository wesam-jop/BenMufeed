import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Lock, Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import type { CmsPagePayload } from '@/types/cms-page';
import { cn } from '@/lib/utils';

type PageRow = CmsPagePayload & {
    display_path?: string;
    group?: string;
    group_label?: string;
    can_delete?: boolean;
    can_unpublish?: boolean;
    is_protected?: boolean;
};

type PageGroup = {
    key: string;
    label: string;
    pages: PageRow[];
};

type Props = {
    pages: PageRow[];
    groups: PageGroup[];
    locales?: Array<{ value: string; label: string }>;
};

export default function CmsPagesIndex({ pages, groups, locales = [] }: Props) {
    const { t } = useI18n();
    const [localeFilter, setLocaleFilter] = useState<'all' | string>('all');

    const filteredGroups = useMemo(() => {
        const source = groups?.length ? groups : [{ key: 'all', label: t('admin.pages.title'), pages }];
        if (localeFilter === 'all') return source;

        return source
            .map((group) => ({
                ...group,
                pages: group.pages.filter((p) => (p.locale || 'ar') === localeFilter),
            }))
            .filter((g) => g.pages.length > 0);
    }, [groups, pages, localeFilter, t]);

    return (
        <>
            <Head title={t('admin.pages.title')} />
            <PageHeader
                title={t('admin.pages.title')}
                description={t('admin.pages.description')}
                actions={
                    <Button asChild>
                        <Link href="/admin/pages/create">
                            <Plus className="size-4" />
                            {t('admin.pages.new')}
                        </Link>
                    </Button>
                }
            />

            <div className="mb-5 flex flex-wrap items-center gap-2 rounded-xl border bg-amber-50/70 px-4 py-3 text-sm text-amber-950">
                <ShieldCheck className="size-4 shrink-0" />
                <span>{t('admin.pages.system_hint')}</span>
            </div>

            <div className="mb-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={() => setLocaleFilter('all')}
                    className={cn(
                        'rounded-lg border px-3 py-1.5 text-sm',
                        localeFilter === 'all'
                            ? 'border-stone-900 bg-stone-900 text-white'
                            : 'bg-background hover:bg-muted/40',
                    )}
                >
                    {t('admin.pages.filter_all')}
                </button>
                {(locales.length
                    ? locales
                    : [
                          { value: 'ar', label: t('common.arabic') },
                          { value: 'en', label: t('common.english') },
                      ]
                ).map((opt) => (
                    <button
                        key={opt.value}
                        type="button"
                        onClick={() => setLocaleFilter(opt.value)}
                        className={cn(
                            'rounded-lg border px-3 py-1.5 text-sm',
                            localeFilter === opt.value
                                ? 'border-stone-900 bg-stone-900 text-white'
                                : 'bg-background hover:bg-muted/40',
                        )}
                    >
                        {opt.label}
                    </button>
                ))}
            </div>

            <div className="space-y-5">
                {filteredGroups.map((group) => (
                    <Card key={group.key}>
                        <CardHeader className="border-b py-3">
                            <CardTitle className="text-base">{group.label}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/40 text-start">
                                            <th className="px-4 py-3 font-medium">
                                                {t('admin.pages.page_title')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('common.language')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('common.path')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('common.status')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('common.blocks')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('common.actions')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {group.pages.map((page) => (
                                            <tr
                                                key={page.id}
                                                className="border-b hover:bg-muted/20"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span>{page.title}</span>
                                                        {page.is_system ? (
                                                            <Badge
                                                                variant="outline"
                                                                className="gap-1"
                                                            >
                                                                <Lock className="size-3" />
                                                                {t('admin.pages.protected')}
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="secondary">
                                                                {t('admin.pages.custom')}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline">
                                                        {(page.locale || 'ar') === 'en'
                                                            ? 'EN'
                                                            : 'AR'}
                                                    </Badge>
                                                </td>
                                                <td
                                                    className="px-4 py-3 font-mono text-xs"
                                                    dir="ltr"
                                                >
                                                    {page.display_path ??
                                                        (page.path === 'home'
                                                            ? '/'
                                                            : `/${page.path}`)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            page.is_published
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {page.is_published
                                                            ? t('common.published')
                                                            : t('common.draft')}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {page.sections_count ?? 0}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-wrap gap-1">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/admin/pages/${page.id}/builder`}
                                                            >
                                                                <Pencil className="size-3.5" />
                                                                {t('admin.pages.builder')}
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <a
                                                                href={page.public_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                title={t('common.preview')}
                                                            >
                                                                <ExternalLink className="size-3.5" />
                                                            </a>
                                                        </Button>
                                                        {page.can_unpublish ? (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    router.post(
                                                                        `/admin/pages/${page.id}/toggle-publish`,
                                                                    )
                                                                }
                                                            >
                                                                {page.is_published
                                                                    ? t('admin.pages.unpublish')
                                                                    : t('admin.pages.publish')}
                                                            </Button>
                                                        ) : null}
                                                        {!page.is_system ? (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="border-destructive/30 text-destructive hover:bg-destructive/10"
                                                                onClick={() => {
                                                                    if (
                                                                        confirm(
                                                                            t(
                                                                                'admin.pages.delete_confirm',
                                                                            ),
                                                                        )
                                                                    ) {
                                                                        router.delete(
                                                                            `/admin/pages/${page.id}`,
                                                                        );
                                                                    }
                                                                }}
                                                            >
                                                                <Trash2 className="size-3.5" />
                                                                {t('common.delete')}
                                                            </Button>
                                                        ) : (
                                                            <span
                                                                className="inline-flex items-center gap-1 px-2 text-[11px] text-muted-foreground"
                                                                title={t('admin.pages.protected')}
                                                            >
                                                                <Lock className="size-3" />
                                                                {t('admin.pages.protected')}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </>
    );
}
