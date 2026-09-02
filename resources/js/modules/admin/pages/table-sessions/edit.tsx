import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    tableSession: {
        id: number;
        guest_count?: number | null;
    };
};

export default function TableSessionsEdit({ tableSession }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        guest_count: tableSession.guest_count ?? 1,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/table-sessions/${tableSession.id}`);
    };

    return (
        <>
            <Head title="تعديل جلسة طاولة" />
            <PageHeader title="تعديل جلسة طاولة" description="تحديث بيانات الجلسة" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-xl gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="guest_count">عدد الضيوف</Label>
                            <Input
                                id="guest_count"
                                type="number"
                                min={1}
                                value={data.guest_count}
                                onChange={(e) => setData('guest_count', Number(e.target.value))}
                            />
                            <InputError message={errors.guest_count} />
                        </div>
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/table-sessions/${tableSession.id}`}>إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
