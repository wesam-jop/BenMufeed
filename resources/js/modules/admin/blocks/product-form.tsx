import { Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
import InputError from '@/components/input-error';
import ImageUploadField from '@/components/admin/image-upload-field';
import MaterialSearchSelect from '@/components/admin/material-search-select';
import SearchSelect from '@/components/admin/search-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';

export type CategoryOption = { id: number; name: string };

export type MaterialOption = {
    id: number;
    name: string;
    cost_price: number;
    unit: { name: string; abbreviation: string } | null;
};

export type RecipeItemRow = {
    ingredient_product_id: string;
    quantity: number;
    waste_percent: number;
};

type ProductFormData = {
    name: string;
    sku: string;
    category_id: string;
    description: string;
    sale_price: number;
    cost_price: number;
    image: File | null;
    recipe_items: RecipeItemRow[];
};

type ProductFormErrors = Partial<Record<keyof ProductFormData | `recipe_items.${number}.${keyof RecipeItemRow}`, string>>;

type Props = {
    data: ProductFormData;
    errors: ProductFormErrors;
    processing: boolean;
    materials: MaterialOption[];
    categories: CategoryOption[];
    imageUrl?: string | null;
    submitLabel: string;
    cancelHref: string;
    setData: <K extends keyof ProductFormData>(key: K, value: ProductFormData[K]) => void;
    onSubmit: (e: React.FormEvent) => void;
};

function calculateRecipeCost(items: RecipeItemRow[], materials: MaterialOption[]): number {
    return items.reduce((total, row) => {
        if (!row.ingredient_product_id) {
            return total;
        }

        const material = materials.find((item) => item.id === Number(row.ingredient_product_id));
        if (!material) {
            return total;
        }

        const quantity = Number(row.quantity) || 0;
        const waste = Number(row.waste_percent) || 0;
        const requiredQty = quantity * (1 + waste / 100);

        return total + requiredQty * material.cost_price;
    }, 0);
}

export default function ProductForm({
    data,
    errors,
    processing,
    materials,
    categories,
    imageUrl,
    submitLabel,
    cancelHref,
    setData,
    onSubmit,
}: Props) {
    const computedCost = useMemo(
        () => calculateRecipeCost(data.recipe_items, materials),
        [data.recipe_items, materials],
    );

    const hasRecipe = data.recipe_items.some((item) => item.ingredient_product_id);

    const categoryOptions = useMemo(
        () =>
            categories.map((category) => ({
                value: String(category.id),
                label: category.name,
            })),
        [categories],
    );

    const addRecipeRow = () => {
        setData('recipe_items', [
            ...data.recipe_items,
            { ingredient_product_id: '', quantity: 1, waste_percent: 0 },
        ]);
    };

    const updateRecipeRow = (index: number, field: keyof RecipeItemRow, value: string | number) => {
        const next = [...data.recipe_items];
        next[index] = { ...next[index], [field]: value };
        setData('recipe_items', next);

        if (field !== 'waste_percent' || hasRecipe) {
            setData('cost_price', Number(calculateRecipeCost(next, materials).toFixed(4)));
        }
    };

    const removeRecipeRow = (index: number) => {
        const next = data.recipe_items.filter((_, rowIndex) => rowIndex !== index);
        setData('recipe_items', next);
        setData('cost_price', Number(calculateRecipeCost(next, materials).toFixed(4)));
    };

    return (
        <form onSubmit={onSubmit} className="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div className="grid gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>معلومات الطبق</CardTitle>
                        <CardDescription>الاسم، الصورة، الوصف، والقسم</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم الطبق *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="h-11 text-base"
                                placeholder="مثال: برجر لحم، كابتشينو..."
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="category_id">القسم *</Label>
                            <SearchSelect
                                id="category_id"
                                options={categoryOptions}
                                value={data.category_id}
                                onChange={(value) => setData('category_id', value)}
                                placeholder="ابحث عن القسم أو اختر من القائمة..."
                                emptyMessage="لا يوجد قسم بهذا الاسم"
                            />
                            <InputError message={errors.category_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">الوصف</Label>
                            <textarea
                                id="description"
                                rows={4}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className={cn(
                                    'flex min-h-[120px] w-full rounded-lg border border-input bg-background px-4 py-3 text-base',
                                    'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                )}
                                placeholder="وصف الطبق للعرض في نقطة البيع والموقع"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid gap-2">
                            <Label>صورة المنتج</Label>
                            <p className="text-xs text-muted-foreground">
                                تظهر في المتجر ونقطة البيع — يُفضّل صورة مربعة أو عمودية واضحة
                            </p>
                            <ImageUploadField
                                value={data.image}
                                previewUrl={imageUrl}
                                onChange={(file) => setData('image', file)}
                                title="اسحب صورة المنتج هنا"
                                hint="PNG أو JPG — حتى 4 ميجابايت"
                                aspectClass="aspect-[4/5]"
                            />
                            <InputError message={errors.image} />
                        </div>

                        <div className="grid max-w-md gap-2">
                            <Label htmlFor="sku">رمز SKU (اختياري)</Label>
                            <Input
                                id="sku"
                                value={data.sku}
                                onChange={(e) => setData('sku', e.target.value)}
                                className="h-11 font-mono text-base"
                                placeholder="PROD-001"
                                dir="ltr"
                            />
                            <InputError message={errors.sku} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4">
                        <div>
                            <CardTitle>مواد التكلفة من المخزون</CardTitle>
                            <CardDescription>
                                اختر المواد وكمياتها لحساب تكلفة الطبق تلقائياً
                            </CardDescription>
                        </div>
                        <Button type="button" variant="outline" size="sm" asChild>
                            <Link href="/admin/inventory/materials/create" target="_blank">
                                <Plus className="size-4" />
                                مادة جديدة
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        {materials.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                لا توجد مواد في المخزون بعد.{' '}
                                <Link
                                    href="/admin/inventory/materials/create"
                                    className="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    أضف مواد المخزون أولاً
                                </Link>
                            </div>
                        ) : (
                            <>
                                {data.recipe_items.map((row, index) => {
                                    const material = materials.find(
                                        (item) => item.id === Number(row.ingredient_product_id),
                                    );
                                    const lineCost =
                                        material && row.quantity
                                            ? row.quantity *
                                              (1 + (row.waste_percent || 0) / 100) *
                                              material.cost_price
                                            : 0;

                                    return (
                                        <div
                                            key={index}
                                            className="grid gap-4 rounded-lg border p-4"
                                        >
                                            <div className="grid gap-2">
                                                <Label>المادة</Label>
                                                <MaterialSearchSelect
                                                    materials={materials}
                                                    value={row.ingredient_product_id}
                                                    onChange={(value) =>
                                                        updateRecipeRow(
                                                            index,
                                                            'ingredient_product_id',
                                                            value,
                                                        )
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[140px_120px_1fr_auto] lg:items-end">
                                            <div className="grid gap-2">
                                                <Label>الكمية</Label>
                                                <Input
                                                    type="number"
                                                    min="0.0001"
                                                    step="0.01"
                                                    value={String(row.quantity)}
                                                    onChange={(e) =>
                                                        updateRecipeRow(
                                                            index,
                                                            'quantity',
                                                            Number(e.target.value),
                                                        )
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label>هدر %</Label>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="1"
                                                    value={String(row.waste_percent)}
                                                    onChange={(e) =>
                                                        updateRecipeRow(
                                                            index,
                                                            'waste_percent',
                                                            Number(e.target.value),
                                                        )
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label>تكلفة السطر</Label>
                                                <div className="flex h-11 items-center rounded-md border bg-muted/40 px-3 text-sm">
                                                    {formatCurrency(lineCost)}
                                                </div>
                                            </div>

                                            <div className="flex items-end sm:col-span-2 lg:col-span-1">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-11"
                                                    onClick={() => removeRecipeRow(index)}
                                                    aria-label="حذف المادة"
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                            </div>
                                        </div>
                                    );
                                })}

                                <Button type="button" variant="outline" onClick={addRecipeRow}>
                                    <Plus className="size-4" />
                                    إضافة مادة
                                </Button>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 self-start">
                <Card>
                    <CardHeader>
                        <CardTitle>التسعير</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="sale_price">سعر البيع *</Label>
                            <Input
                                id="sale_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={String(data.sale_price)}
                                onChange={(e) => setData('sale_price', Number(e.target.value))}
                                className="h-11 text-base"
                                required
                            />
                            <InputError message={errors.sale_price} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="cost_price">تكلفة الطبق</Label>
                            <Input
                                id="cost_price"
                                type="number"
                                min="0"
                                step="0.0001"
                                value={String(hasRecipe ? computedCost : data.cost_price)}
                                readOnly={hasRecipe}
                                onChange={(e) => setData('cost_price', Number(e.target.value))}
                                className={cn('h-11 text-base', hasRecipe && 'bg-muted')}
                            />
                            {hasRecipe && (
                                <p className="text-xs text-muted-foreground">
                                    تُحسب تلقائياً من مواد المخزون
                                </p>
                            )}
                            <InputError message={errors.cost_price} />
                        </div>

                        {data.sale_price > 0 && (hasRecipe ? computedCost : data.cost_price) > 0 && (
                            <div className="rounded-lg bg-muted/50 p-3 text-sm">
                                <span className="text-muted-foreground">هامش الربح: </span>
                                <span className="font-medium">
                                    {formatCurrency(data.sale_price - (hasRecipe ? computedCost : data.cost_price))}
                                </span>
                            </div>
                        )}

                        <div className="flex flex-col gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {submitLabel}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={cancelHref}>إلغاء</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </form>
    );
}

export { calculateRecipeCost };
