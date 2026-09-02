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
    refund: Item;
};

export default function RefundsEdit({ refund }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        reason: (refund.reason as string) ?? '',
        amount: (refund.amount as number) ?? 0,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/refunds/:id'.replace(':id', String(refund.id)));
    };

    return (
        <>
            <Head title="تعديل مرتجعات" />
            <PageHeader title="تعديل مرتجعات" description="تعديل مرتجعات" />
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
                                تحديث
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
