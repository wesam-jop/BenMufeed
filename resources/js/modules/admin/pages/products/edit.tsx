import { Head, useForm } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import ProductForm, {
    type CategoryOption,
    type MaterialOption,
    type RecipeItemRow,
    calculateRecipeCost,
} from '@/components/admin/product-form';

type ProductPayload = {
    id: number;
    name: string;
    sku: string | null;
    category_id: number;
    description: string | null;
    sale_price: number;
    cost_price: number;
    image_url: string | null;
};

type Props = {
    product: ProductPayload;
    categories: CategoryOption[];
    materials: MaterialOption[];
    recipe_items: RecipeItemRow[];
};

export default function ProductsEdit({ product, categories, materials, recipe_items }: Props) {
    const { data, setData, post, processing, errors, transform } = useForm({
        name: product.name,
        sku: product.sku ?? '',
        category_id: String(product.category_id),
        description: product.description ?? '',
        sale_price: product.sale_price,
        cost_price: product.cost_price,
        image: null as File | null,
        recipe_items: recipe_items.map((row) => ({
            ingredient_product_id: String(row.ingredient_product_id),
            quantity: row.quantity,
            waste_percent: row.waste_percent,
        })),
        _method: 'put',
    });

    transform((formData) => {
        const recipeItems = formData.recipe_items.filter((row) => row.ingredient_product_id);
        const computedCost = calculateRecipeCost(recipeItems, materials);

        return {
            ...formData,
            recipe_items: recipeItems,
            cost_price: recipeItems.length > 0 ? computedCost : formData.cost_price,
        };
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/admin/products/${product.id}`, { forceFormData: true });
    };

    return (
        <>
            <Head title="تعديل طبق" />
            <PageHeader title="تعديل طبق" description="عدّل بيانات الطبق ومواد التكلفة" />
            <ProductForm
                data={data}
                errors={errors}
                processing={processing}
                materials={materials}
                categories={categories}
                imageUrl={product.image_url}
                submitLabel="تحديث الطبق"
                cancelHref="/admin/products"
                setData={setData}
                onSubmit={submit}
            />
        </>
    );
}
