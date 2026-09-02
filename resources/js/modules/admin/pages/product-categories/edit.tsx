import { Head, Link, useForm } from '@inertiajs/react';
import ImageUploadField from '@/components/admin/image-upload-field';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';

type Category = {
    id: number;
    name: string;
    sort_order?: number;
    is_active?: boolean;
    image_url?: string | null;
};

type Props = {
    productCategory: Category;
};

export default function ProductCategoriesEdit({ productCategory }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: productCategory.name ?? '',
        sort_order: productCategory.sort_order ?? 0,
        is_active: productCategory.is_active ?? true,
        image: null as File | null,
        remove_image: false,
        _method: 'put',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/admin/product-categories/${productCategory.id}`, { forceFormData: true });
    };

    return (
        <>
            <Head title={`تعديل ${productCategory.name}`} />
            <PageHeader title={`تعديل: ${productCategory.name}`} description="حدّث بيانات وصورة القسم" />

            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>صورة القسم</CardTitle>
                        <CardDescription>
                            تظهر في بطاقات الأقسام بالمتجر — اختر صورة واضحة وجذابة
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ImageUploadField
                            value={data.image}
                            previewUrl={data.remove_image ? null : productCategory.image_url}
                            onChange={(file) => {
                                setData('image', file);
                                setData(
                                    'remove_image',
                                    file === null && Boolean(productCategory.image_url),
                                );
                            }}
                            title="اسحب صورة القسم هنا"
                            hint="PNG أو JPG — حتى 4 ميجابايت"
                            aspectClass="aspect-[4/3]"
                        />
                        <InputError message={errors.image} className="mt-2" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>بيانات القسم</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم القسم *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="sort_order">الترتيب</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                min={0}
                                value={data.sort_order}
                                onChange={(e) => setData('sort_order', Number(e.target.value))}
                            />
                            <InputError message={errors.sort_order} />
                        </div>
                        <label className="flex items-center gap-3 rounded-lg border px-4 py-3">
                            <Checkbox
                                checked={data.is_active}
                                onCheckedChange={(v) => setData('is_active', Boolean(v))}
                            />
                            <span>
                                <span className="block text-sm font-medium">ظهور في المتجر</span>
                                <span className="block text-xs text-muted-foreground">تفعيل القسم للزبائن</span>
                            </span>
                        </label>
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                حفظ التعديلات
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/product-categories">إلغاء</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </>
    );
}
