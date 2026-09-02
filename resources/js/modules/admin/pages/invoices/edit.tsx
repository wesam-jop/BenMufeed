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
    invoice: Item;
};

export default function InvoicesEdit({ invoice }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        notes: (invoice.notes as string) ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/invoices/:id'.replace(':id', String(invoice.id)));
    };

    return (
        <>
            <Head title="تعديل فواتires" />
            <PageHeader title="تعديل فواتires" description="تعديل فواتires" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-xl gap-4">
                        
                        <div className="grid gap-2">
                            <Label htmlFor="notes">ملاحظات</Label>
                            <Input
                                id="notes"
                                type="text"
                                value={String(data.notes ?? '')}
                                onChange={(e) => setData('notes', e.target.value)}
                                
                            />
                            <InputError message={errors.notes} />
                        </div>
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/invoices">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
