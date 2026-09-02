import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import type { Paginated } from '@/types/admin';

type Item = Record<string, unknown> & { id: number };

type Props = {
    modifierGroup: Paginated<Item>;
};

export default function ModifierGroupsIndex({ modifierGroup }: Props) {
    const { t } = useI18n();
    const items = modifierGroup.data ?? [];

    return (
        <>
            <Head title={t('admin.modifiers.title')} />
            <PageHeader
                title={t('admin.modifiers.title')}
                description={t('admin.modifiers.description')}
                createHref="/admin/modifier-groups/create"
                createLabel={t('admin.modifiers.create')}
            />
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">{t('common.name')}</th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.modifiers.min_selections')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.modifiers.max_selections')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            {t('admin.modifiers.empty')}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                {String(item.name ?? '—')}
                                            </td>
                                            <td className="px-4 py-3">
                                                {String(item.min_selections ?? '—')}
                                            </td>
                                            <td className="px-4 py-3">
                                                {String(item.max_selections ?? '—')}
                                            </td>
                                            <td className="px-4 py-3">
                                                <ResourceActions
                                                    editHref={`/admin/modifier-groups/${item.id}/edit`}
                                                    deleteHref={`/admin/modifier-groups/${item.id}`}
                                                    editPermission="modifier-groups.update"
                                                    deletePermission="modifier-groups.delete"
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={modifierGroup} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
