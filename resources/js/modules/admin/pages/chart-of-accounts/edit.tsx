import { Head, Link, useForm } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type TypeOption = { value: string; label: string };
type ParentOption = { id: number; code: string; name: string; type_label: string };

type AccountDetail = {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    parent_id: number | null;
    is_system: boolean;
    is_active: boolean;
};

type Props = {
    account: AccountDetail;
    typeOptions: TypeOption[];
    parentOptions: ParentOption[];
};

export default function ChartOfAccountsEdit({ account, typeOptions, parentOptions }: Props) {
    const { data, setData, put, processing, errors, transform } = useForm({
        code: account.code,
        name: account.name,
        type: account.type,
        parent_id: account.parent_id ? String(account.parent_id) : '',
        is_active: account.is_active,
    });

    transform((form) => ({
        ...form,
        parent_id: form.parent_id ? Number(form.parent_id) : null,
    }));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/chart-of-accounts/${account.id}`);
    };

    return (
        <>
            <Head title={`تعديل ${account.name}`} />
            <PageHeader
                title={`تعديل ${account.name}`}
                description={
                    account.is_system
                        ? 'حساب نظامي — يمكن تعديل الاسم والحالة فقط'
                        : 'تعديل بيانات الحساب المحاسبي'
                }
            />

            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <BookOpen className="size-5" />
                        بيانات الحساب
                        {account.is_system && <Badge variant="outline">نظامي</Badge>}
                    </CardTitle>
                    <CardDescription>
                        {account.is_system
                            ? 'الرمز والنوع محميان لأن هذا حساب نظامي'
                            : 'الرمز والاسم والنوع إلزامية'}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        {!account.is_system && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="code">رمز الحساب *</Label>
                                    <Input
                                        id="code"
                                        type="text"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.code} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="type">نوع الحساب *</Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) => setData('type', value)}
                                    >
                                        <SelectTrigger id="type" className="h-11 w-full">
                                            <SelectValue placeholder="اختر النوع" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {typeOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.type} />
                                </div>
                            </div>
                        )}

                        {account.is_system && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <DetailField label="رمز الحساب" value={account.code} />
                                <DetailField label="نوع الحساب" value={account.type_label} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="name">اسم الحساب *</Label>
                            <Input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        {!account.is_system && (
                            <div className="grid gap-2">
                                <Label htmlFor="parent_id">الحساب الأب (اختياري)</Label>
                                <Select
                                    value={data.parent_id || '__none__'}
                                    onValueChange={(value) =>
                                        setData('parent_id', value === '__none__' ? '' : value)
                                    }
                                >
                                    <SelectTrigger id="parent_id" className="h-11 w-full">
                                        <SelectValue placeholder="بدون حساب أب" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">بدون حساب أب</SelectItem>
                                        {parentOptions.map((option) => (
                                            <SelectItem key={option.id} value={String(option.id)}>
                                                {option.code} — {option.name} ({option.type_label})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.parent_id} />
                            </div>
                        )}

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked === true)
                                }
                            />
                            <Label htmlFor="is_active" className="font-normal">
                                حساب نشط
                            </Label>
                        </div>
                        <InputError message={errors.is_active} />

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing || !data.name}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/chart-of-accounts/${account.id}`}>إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}

function DetailField({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="font-medium">{value}</p>
        </div>
    );
}
