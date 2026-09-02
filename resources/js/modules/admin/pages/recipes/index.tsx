import { Head, Link } from '@inertiajs/react';
import { ChevronDown, ExternalLink, UtensilsCrossed } from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency, formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/admin';

type RecipeItemRow = {
    ingredient_name: string;
    quantity: number;
    waste_percent: number;
    unit: string | null;
    line_cost: number;
};

type RecipeRow = {
    id: number;
    name: string;
    version: number;
    yield_quantity: number;
    items_count: number;
    computed_cost: number;
    items: RecipeItemRow[];
    product: {
        id: number;
        name: string;
        cost_price: number;
        sale_price: number;
        category: string | null;
    };
};

type Props = {
    recipes: Paginated<RecipeRow>;
};

export default function RecipesIndex({ recipes }: Props) {
    const { t } = useI18n();
    const items = recipes.data ?? [];
    const [expandedId, setExpandedId] = useState<number | null>(null);

    return (
        <>
            <Head title={t('admin.nav.recipes')} />
            <PageHeader
                title={t('admin.nav.recipes')}
                description={t('admin.recipes.description')}
            />

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <UtensilsCrossed className="size-8 text-muted-foreground" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">{t('admin.recipes.empty')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.recipes.empty_hint')}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/admin/products/create">
                                {t('admin.recipes.add_product')}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-3">
                    {items.map((recipe) => {
                        const isOpen = expandedId === recipe.id;
                        const margin =
                            recipe.product.sale_price > 0
                                ? recipe.product.sale_price - recipe.computed_cost
                                : null;

                        return (
                            <Collapsible
                                key={recipe.id}
                                open={isOpen}
                                onOpenChange={(open) => setExpandedId(open ? recipe.id : null)}
                            >
                                <Card className={cn(isOpen && 'border-primary/30 shadow-sm')}>
                                    <div className="flex flex-col gap-4 p-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div className="min-w-0 flex-1 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="text-lg font-semibold">
                                                    {recipe.product.name}
                                                </h2>
                                                {recipe.product.category && (
                                                    <Badge variant="secondary">
                                                        {recipe.product.category}
                                                    </Badge>
                                                )}
                                                <Badge variant="outline">
                                                    {t('admin.recipes.ingredients_count').replace(
                                                        ':count',
                                                        String(recipe.items_count),
                                                    )}
                                                </Badge>
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {recipe.name}
                                                {recipe.version > 1
                                                    ? ` — ${t('admin.recipes.version').replace(':version', String(recipe.version))}`
                                                    : ''}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-4 lg:gap-6">
                                            <div className="text-right">
                                                <p className="text-xs text-muted-foreground">
                                                    {t('admin.recipes.cost')}
                                                </p>
                                                <p className="font-semibold">
                                                    {formatCurrency(recipe.computed_cost)}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-xs text-muted-foreground">
                                                    {t('admin.recipes.sale_price')}
                                                </p>
                                                <p className="font-semibold">
                                                    {formatCurrency(recipe.product.sale_price)}
                                                </p>
                                            </div>
                                            {margin !== null && (
                                                <div className="text-right">
                                                    <p className="text-xs text-muted-foreground">
                                                        {t('admin.recipes.profit')}
                                                    </p>
                                                    <p
                                                        className={cn(
                                                            'font-semibold',
                                                            margin >= 0
                                                                ? 'text-emerald-600'
                                                                : 'text-destructive',
                                                        )}
                                                    >
                                                        {formatCurrency(margin)}
                                                    </p>
                                                </div>
                                            )}

                                            <div className="flex items-center gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link
                                                        href={`/admin/products/${recipe.product.id}/edit`}
                                                    >
                                                        <ExternalLink className="size-4" />
                                                        {t('admin.recipes.edit_product')}
                                                    </Link>
                                                </Button>
                                                <CollapsibleTrigger asChild>
                                                    <Button variant="ghost" size="sm">
                                                        <ChevronDown
                                                            className={cn(
                                                                'size-4 transition-transform',
                                                                isOpen && 'rotate-180',
                                                            )}
                                                        />
                                                        {t('admin.recipes.details')}
                                                    </Button>
                                                </CollapsibleTrigger>
                                            </div>
                                        </div>
                                    </div>

                                    <CollapsibleContent>
                                        <div className="border-t px-4 pb-4">
                                            <div className="overflow-x-auto pt-4">
                                                <table className="w-full text-sm">
                                                    <thead>
                                                        <tr className="border-b bg-muted/40 text-right">
                                                            <th className="px-3 py-2 font-medium">
                                                                {t('admin.recipes.ingredient')}
                                                            </th>
                                                            <th className="px-3 py-2 font-medium">
                                                                {t('admin.recipes.quantity')}
                                                            </th>
                                                            <th className="px-3 py-2 font-medium">
                                                                {t('admin.recipes.waste')}
                                                            </th>
                                                            <th className="px-3 py-2 font-medium">
                                                                {t('admin.recipes.line_cost')}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {recipe.items.map((item, index) => (
                                                            <tr
                                                                key={index}
                                                                className="border-b last:border-0"
                                                            >
                                                                <td className="px-3 py-2">
                                                                    {item.ingredient_name}
                                                                </td>
                                                                <td className="px-3 py-2">
                                                                    {formatQuantity(item.quantity)}
                                                                    {item.unit
                                                                        ? ` ${item.unit}`
                                                                        : ''}
                                                                </td>
                                                                <td className="px-3 py-2">
                                                                    {item.waste_percent > 0
                                                                        ? `${item.waste_percent}%`
                                                                        : '—'}
                                                                </td>
                                                                <td className="px-3 py-2 font-medium">
                                                                    {formatCurrency(item.line_cost)}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                    <tfoot>
                                                        <tr className="bg-muted/20 font-semibold">
                                                            <td
                                                                colSpan={3}
                                                                className="px-3 py-2 text-left"
                                                            >
                                                                {t('admin.recipes.total_cost')}
                                                            </td>
                                                            <td className="px-3 py-2">
                                                                {formatCurrency(
                                                                    recipe.computed_cost,
                                                                )}
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </Card>
                            </Collapsible>
                        );
                    })}

                    <PaginationLinks paginator={recipes} />
                </div>
            )}
        </>
    );
}
