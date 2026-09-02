import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Item = Record<string, unknown> & { id: number };

type Props = {
    modifierGroup: Item;
};

export default function ModifierGroupsEdit({ modifierGroup }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: (modifierGroup.name as string) ?? '',
        min_selections: (modifierGroup.min_selections as number) ?? 0,
        max_selections: (modifierGroup.max_selections as number) ?? 1,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/modifier-groups/:id'.replace(':id', String(modifierGroup.id)));
    };

    return (
        <>
            <Head title="تعديل إضافات" />
            <PageHeader title="تعديل إضافات" description="تعديل إضافات" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-xl gap-4">
                        
                        <div className="grid gap-2">
                            <Label htmlFor="name">الاسم</Label>
                            <Input
                                id="name"
                                type="text"
                                value={String(data.name ?? '')}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="min_selections">الحد الأدنى</Label>
                            <Input
                                id="min_selections"
                                type="number"
                                value={String(data.min_selections ?? '')}
                                onChange={(e) => setData('min_selections', Number(e.target.value))}
                                
                            />
                            <InputError message={errors.min_selections} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="max_selections">الحد الأقصى</Label>
                            <Input
                                id="max_selections"
                                type="number"
                                value={String(data.max_selections ?? '')}
                                onChange={(e) => setData('max_selections', Number(e.target.value))}
                                
                            />
                            <InputError message={errors.max_selections} />
                        </div>
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/modifier-groups">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
