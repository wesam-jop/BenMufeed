import { Head, router, useForm } from '@inertiajs/react';
import { Coins, Plus, Save, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import StatCard from '@/components/domain/StatCard';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { cn } from '@/lib/utils';

type CurrencyItem = {
    id: number;
    code: string;
    name: string;
    symbol: string;
    symbol_position: 'before' | 'after';
    decimal_places: number;
    rate_to_primary: number;
    is_primary: boolean;
    show_on_receipt: boolean;
    is_active: boolean;
    sort_order: number;
    example: string;
};

type Props = {
    currencies: CurrencyItem[];
    primary: CurrencyItem;
    summary: {
        total: number;
        active: number;
        on_receipt: number;
    };
};

const emptyForm = {
    code: '',
    name: '',
    symbol: '',
    symbol_position: 'after' as 'before' | 'after',
    decimal_places: 2,
    rate_to_primary: 1,
    show_on_receipt: false,
    is_active: true,
    sort_order: 0,
};

export default function CurrenciesSettingsPage({ currencies, primary, summary }: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const createForm = useForm({ ...emptyForm });
    const editForm = useForm({ ...emptyForm });

    const startEdit = (currency: CurrencyItem) => {
        setEditingId(currency.id);
        editForm.setData({
            code: currency.code,
            name: currency.name,
            symbol: currency.symbol,
            symbol_position: currency.symbol_position,
            decimal_places: currency.decimal_places,
            rate_to_primary: currency.rate_to_primary,
            show_on_receipt: currency.show_on_receipt,
            is_active: currency.is_active,
            sort_order: currency.sort_order,
        });
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/settings/currencies', {
            preserveScroll: true,
            onSuccess: () => createForm.reset(),
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingId) {
            return;
        }
        editForm.put(`/admin/settings/currencies/${editingId}`, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    };

    return (
        <>
            <Head title="التصريف" />
            <PageHeader
                title="التصريف"
                description="العملة الرئيسية دولار ($). عدّل أسعار التركي والسوري لتظهر الثلاث عملات على فاتورة البيع"
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard title="العملة الرئيسية" value={primary.code} format="text" icon={Star} description={primary.name} />
                <StatCard title="العملات" value={summary.total} format="number" icon={Coins} description={`${summary.active} نشطة`} />
                <StatCard
                    title="تصريف على الفاتورة"
                    value={summary.on_receipt}
                    format="number"
                    icon={Coins}
                    description="تركي + سوري يظهران مع الدولار"
                />
            </div>

            <div className="mb-6 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3 text-sm text-amber-950">
                <p className="font-medium">كيف يعمل التصريف؟</p>
                <p className="mt-1 text-amber-900/80">
                    كل الأسعار بالدولار. عند طباعة الفاتورة يظهر الإجمالي بالدولار + ما يعادله بالتركي والسوري.
                    أدخل هنا: <strong>كم يساوي 1 دولار</strong> من هذه العملة.
                    مثال: إذا الدولار = 12٬500 ل.س، ضع <span dir="ltr">12500</span> لليرة السورية.
                </p>
            </div>

            <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
                <div className="space-y-4">
                    {currencies.map((currency) => {
                        const editing = editingId === currency.id;

                        return (
                            <Card
                                key={currency.id}
                                className={cn(currency.is_primary && 'border-amber-200 ring-1 ring-amber-100')}
                            >
                                <CardHeader className="flex flex-row items-start justify-between gap-3 pb-3">
                                    <div>
                                        <CardTitle className="flex flex-wrap items-center gap-2 text-base">
                                            {currency.name}
                                            <Badge variant="secondary" dir="ltr">
                                                {currency.code}
                                            </Badge>
                                            {currency.is_primary && <Badge>رئيسية</Badge>}
                                            {currency.show_on_receipt && (
                                                <Badge variant="outline">على الفاتورة</Badge>
                                            )}
                                            {!currency.is_active && (
                                                <Badge variant="destructive">معطّلة</Badge>
                                            )}
                                        </CardTitle>
                                        <p className="mt-1 text-sm text-muted-foreground" dir="ltr">
                                            مثال: {currency.example}
                                            {!currency.is_primary && (
                                                <> · 1 {primary.code} = {currency.rate_to_primary} {currency.code}</>
                                            )}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {!currency.is_primary && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        `/admin/settings/currencies/${currency.id}/make-primary`,
                                                        {},
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                <Star className="size-4" />
                                                اجعلها رئيسية
                                            </Button>
                                        )}
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => (editing ? setEditingId(null) : startEdit(currency))}
                                        >
                                            {editing ? 'إغلاق' : 'تعديل'}
                                        </Button>
                                        {!currency.is_primary && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    router.delete(`/admin/settings/currencies/${currency.id}`, {
                                                        preserveScroll: true,
                                                    })
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        )}
                                    </div>
                                </CardHeader>

                                {editing && (
                                    <CardContent>
                                        <form onSubmit={submitEdit} className="grid gap-4 sm:grid-cols-2">
                                            <CurrencyFields
                                                data={editForm.data}
                                                setData={editForm.setData}
                                                errors={editForm.errors}
                                                isPrimary={currency.is_primary}
                                                primaryCode={primary.code}
                                            />
                                            <div className="sm:col-span-2">
                                                <Button type="submit" disabled={editForm.processing}>
                                                    {editForm.processing && <Spinner />}
                                                    <Save className="size-4" />
                                                    حفظ التعديلات
                                                </Button>
                                            </div>
                                        </form>
                                    </CardContent>
                                )}
                            </Card>
                        );
                    })}
                </div>

                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Plus className="size-4" />
                            إضافة عملة
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitCreate} className="grid gap-4">
                            <CurrencyFields
                                data={createForm.data}
                                setData={createForm.setData}
                                errors={createForm.errors}
                                primaryCode={primary.code}
                            />
                            <Button type="submit" disabled={createForm.processing} className="w-full">
                                {createForm.processing && <Spinner />}
                                إضافة العملة
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function CurrencyFields({
    data,
    setData,
    errors,
    isPrimary = false,
    primaryCode,
}: {
    data: typeof emptyForm;
    setData: (key: keyof typeof emptyForm, value: never) => void;
    errors: Record<string, string>;
    isPrimary?: boolean;
    primaryCode: string;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label>الرمز (ISO)</Label>
                <Input
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value.toUpperCase() as never)}
                    placeholder="USD"
                    dir="ltr"
                    disabled={isPrimary}
                />
                <InputError message={errors.code} />
            </div>
            <div className="grid gap-2">
                <Label>الاسم</Label>
                <Input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value as never)}
                    placeholder="دولار أمريكي"
                />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-2">
                <Label>الشكل / الرمز الظاهر</Label>
                <Input
                    value={data.symbol}
                    onChange={(e) => setData('symbol', e.target.value as never)}
                    placeholder="ر.س أو $"
                />
                <InputError message={errors.symbol} />
            </div>
            <div className="grid gap-2">
                <Label>موضع الرمز</Label>
                <Select
                    value={data.symbol_position}
                    onValueChange={(v) => setData('symbol_position', v as never)}
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="after">بعد الرقم (100 ر.س)</SelectItem>
                        <SelectItem value="before">قبل الرقم ($ 100)</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label>المنازل العشرية</Label>
                <Input
                    type="number"
                    min={0}
                    max={4}
                    value={data.decimal_places}
                    onChange={(e) => setData('decimal_places', Number(e.target.value) as never)}
                />
            </div>
            <div className="grid gap-2">
                <Label>سعر التصريف: 1 {primaryCode} = ؟</Label>
                <Input
                    type="number"
                    step="any"
                    min={0}
                    value={data.rate_to_primary}
                    onChange={(e) => setData('rate_to_primary', Number(e.target.value) as never)}
                    disabled={isPrimary}
                    dir="ltr"
                    placeholder="مثلاً 12500"
                />
                <p className="text-xs text-muted-foreground">
                    كم يساوي دولار واحد بهذه العملة
                </p>
                <InputError message={errors.rate_to_primary} />
            </div>
            {!isPrimary && (
                <>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.show_on_receipt}
                            onCheckedChange={(v) => setData('show_on_receipt', Boolean(v) as never)}
                        />
                        إظهار كتصريف على الفاتورة المطبوعة
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.is_active}
                            onCheckedChange={(v) => setData('is_active', Boolean(v) as never)}
                        />
                        مفعّلة
                    </label>
                </>
            )}
        </>
    );
}
