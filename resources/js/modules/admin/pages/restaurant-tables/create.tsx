import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function RestaurantTablesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        capacity: 4,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/restaurant-tables');
    };

    return (
        <>
            <Head title="إضافة طاولات" />
            <PageHeader title="إضافة طاولات" description="إضافة طاولات جديد" />
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
                            <Label htmlFor="capacity">السعة</Label>
                            <Input
                                id="capacity"
                                type="number"
                                value={String(data.capacity ?? '')}
                                onChange={(e) => setData('capacity', Number(e.target.value))}
                                
                            />
                            <InputError message={errors.capacity} />
                        </div>
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/restaurant-tables">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
