import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function RefundsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        reason: '',
        amount: 0,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/refunds');
    };

    return (
        <>
            <Head title="إضافة مرتجعات" />
            <PageHeader title="إضافة مرتجعات" description="إضافة مرتجعات جديد" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-xl gap-4">
                        
                        <div className="grid gap-2">
                            <Label htmlFor="reason">السبب</Label>
                            <Input
                                id="reason"
                                type="text"
                                value={String(data.reason ?? '')}
                                onChange={(e) => setData('reason', e.target.value)}
                                
                            />
                            <InputError message={errors.reason} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="amount">المبلغ</Label>
                            <Input
                                id="amount"
                                type="number"
                                value={String(data.amount ?? '')}
                                onChange={(e) => setData('amount', Number(e.target.value))}
                                
                            />
                            <InputError message={errors.amount} />
                        </div>
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/refunds">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
