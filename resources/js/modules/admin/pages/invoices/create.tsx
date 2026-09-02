import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function InvoicesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/invoices');
    };

    return (
        <>
            <Head title="إضافة فواتires" />
            <PageHeader title="إضافة فواتires" description="إضافة فواتires جديد" />
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
                                حفظ
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
