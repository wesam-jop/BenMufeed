import { Head, useForm } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import ProductForm, {
    type CategoryOption,
    type MaterialOption,
    type RecipeItemRow,
    calculateRecipeCost,
} from '@/components/admin/product-form';

type Props = {
    categories: CategoryOption[];
    materials: MaterialOption[];
};

export default function ProductsCreate({ categories, materials }: Props) {
    const { data, setData, post, processing, errors, transform } = useForm({
        name: '',
        sku: '',
        category_id: '',
        description: '',
        sale_price: 0,
        cost_price: 0,
        image: null as File | null,
        recipe_items: [] as RecipeItemRow[],
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
        post('/admin/products', { forceFormData: true });
    };

    return (
        <>
            <Head title="إضافة طبق" />
            <PageHeader title="إضافة طبق" description="أضف طبقاً جديداً مع صورة ووصف وتكلفة من المخزون" />
            <ProductForm
                data={data}
                errors={errors}
                processing={processing}
                materials={materials}
                categories={categories}
                submitLabel="حفظ الطبق"
                cancelHref="/admin/products"
                setData={setData}
                onSubmit={submit}
            />
        </>
    );
}
