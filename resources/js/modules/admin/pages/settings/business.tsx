import { Head, Link, useForm } from '@inertiajs/react';
import {
    Building2,
    Clock,
    Globe,
    Mail,
    Phone,
    Receipt,
    Save,
    Settings,
    Store,
} from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import StatCard from '@/components/domain/StatCard';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

type BusinessSettings = {
    business_name: string;
    description: string;
    opening_hours: string;
    phone: string;
    email: string;
    address: string;
    whatsapp: string;
    instagram: string;
    tax_number: string;
    currency: string;
    timezone: string;
    receipt_footer: string;
    updated_at: string | null;
};

type Summary = {
    contact_fields_filled: number;
    core_fields_filled: number;
    has_tax_number: boolean;
    has_receipt_footer: boolean;
    currency: string;
    timezone: string;
    updated_at: string | null;
};

type Option = { value: string; label: string };

type Props = {
    settings: BusinessSettings;
    summary: Summary;
    options: {
        currencies: Option[];
        timezones: Option[];
    };
};

const tabs = [
    { id: 'general', label: 'عام', icon: Building2 },
    { id: 'contact', label: 'التواصل', icon: Phone },
    { id: 'regional', label: 'العملة والوقت', icon: Globe },
    { id: 'receipt', label: 'الفاتورة', icon: Receipt },
];

export default function BusinessSettingsPage({ settings, summary, options }: Props) {
    const [tab, setTab] = useState('general');

    const { data, setData, put, processing, errors } = useForm({
        business_name: settings.business_name ?? '',
        description: settings.description ?? '',
        opening_hours: settings.opening_hours ?? '',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        address: settings.address ?? '',
        whatsapp: settings.whatsapp ?? '',
        instagram: settings.instagram ?? '',
        tax_number: settings.tax_number ?? '',
        currency: settings.currency ?? 'SAR',
        timezone: settings.timezone ?? 'Asia/Riyadh',
        receipt_footer: settings.receipt_footer ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/settings/business', { preserveScroll: true });
    };

    return (
        <>
            <Head title="إعدادات النشاط" />
            <PageHeader
                title="إعدادات النشاط"
                description="بيانات المقهى الداخلية — POS، الفواتير، والعمليات"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/site">
                            <Store className="size-4" />
                            إعدادات الموقع
                        </Link>
                    </Button>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title="البيانات الأساسية"
                    value={summary.core_fields_filled}
                    format="number"
                    icon={Building2}
                    description="من 4 حقول"
                />
                <StatCard
                    title="بيانات التواصل"
                    value={summary.contact_fields_filled}
                    format="number"
                    icon={Mail}
                    description="من 4 حقول"
                />
                <StatCard
                    title="العملة"
                    value={summary.currency}
                    format="text"
                    icon={Settings}
                />
                <StatCard
                    title="المنطقة الزمنية"
                    value={1}
                    format="number"
                    icon={Clock}
                    description={summary.timezone}
                />
            </div>

            {summary.updated_at && (
                <p className="mb-4 text-sm text-muted-foreground">
                    آخر تحديث: {formatDate(summary.updated_at)}
                </p>
            )}

            <div className="mb-4 flex flex-wrap gap-2">
                {summary.has_tax_number && <Badge variant="outline">رقم ضريبي</Badge>}
                {summary.has_receipt_footer && <Badge variant="outline">تذييل فاتورة</Badge>}
            </div>

            <form onSubmit={submit}>
                <div className="mb-4 flex flex-wrap gap-2">
                    {tabs.map((item) => (
                        <Button
                            key={item.id}
                            type="button"
                            variant={tab === item.id ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setTab(item.id)}
                            className={cn(tab === item.id && 'bg-primary')}
                        >
                            <item.icon className="size-4" />
                            {item.label}
                        </Button>
                    ))}
                </div>

                {tab === 'general' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">البيانات الأساسية</CardTitle>
                        </CardHeader>
                        <CardContent className="grid max-w-2xl gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="business_name">اسم النشاط *</Label>
                                <Input
                                    id="business_name"
                                    value={data.business_name}
                                    onChange={(e) => setData('business_name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.business_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">نبذة عن المقهى</Label>
                                <textarea
                                    id="description"
                                    rows={4}
                                    className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="opening_hours">ساعات العمل</Label>
                                <Input
                                    id="opening_hours"
                                    value={data.opening_hours}
                                    onChange={(e) => setData('opening_hours', e.target.value)}
                                    placeholder="يومياً: 7:00 ص – 12:00 م"
                                />
                                <InputError message={errors.opening_hours} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="tax_number">الرقم الضريبي</Label>
                                <Input
                                    id="tax_number"
                                    value={data.tax_number}
                                    onChange={(e) => setData('tax_number', e.target.value)}
                                    dir="ltr"
                                    className="text-left"
                                />
                                <InputError message={errors.tax_number} />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {tab === 'contact' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">بيانات التواصل</CardTitle>
                        </CardHeader>
                        <CardContent className="grid max-w-2xl gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="phone">الهاتف</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    dir="ltr"
                                    className="text-left"
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="whatsapp">واتساب</Label>
                                <Input
                                    id="whatsapp"
                                    value={data.whatsapp}
                                    onChange={(e) => setData('whatsapp', e.target.value)}
                                    dir="ltr"
                                    className="text-left"
                                />
                                <InputError message={errors.whatsapp} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">البريد الإلكتروني</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    dir="ltr"
                                    className="text-left"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="instagram">انستغرام</Label>
                                <Input
                                    id="instagram"
                                    value={data.instagram}
                                    onChange={(e) => setData('instagram', e.target.value)}
                                    dir="ltr"
                                    className="text-left"
                                    placeholder="@kashercafe"
                                />
                                <InputError message={errors.instagram} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="address">العنوان</Label>
                                <textarea
                                    id="address"
                                    rows={3}
                                    className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                />
                                <InputError message={errors.address} />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {tab === 'regional' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">العملة والوقت</CardTitle>
                        </CardHeader>
                        <CardContent className="grid max-w-2xl gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="currency">العملة</Label>
                                <Select
                                    value={data.currency}
                                    onValueChange={(value) => setData('currency', value)}
                                >
                                    <SelectTrigger id="currency" className="h-11 w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.currencies.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.currency} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="timezone">المنطقة الزمنية</Label>
                                <Select
                                    value={data.timezone}
                                    onValueChange={(value) => setData('timezone', value)}
                                >
                                    <SelectTrigger id="timezone" className="h-11 w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.timezones.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.timezone} />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {tab === 'receipt' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">إعدادات الفاتورة</CardTitle>
                        </CardHeader>
                        <CardContent className="grid max-w-2xl gap-4">
                            <div className="rounded-xl border bg-muted/30 px-4 py-3">
                                <p className="text-sm font-medium">محرّر التصميم الكامل</p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    أضف أقساماً، رتّبها، وعدّل النصوص مع معاينة مباشرة للفاتورة الحرارية.
                                </p>
                                <Button type="button" className="mt-3" variant="outline" asChild>
                                    <Link href="/admin/settings/receipt-designer">
                                        <Receipt className="size-4" />
                                        فتح محرّر تصميم الفاتورة
                                    </Link>
                                </Button>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="receipt_footer">تذييل الفاتورة</Label>
                                <textarea
                                    id="receipt_footer"
                                    rows={4}
                                    className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.receipt_footer}
                                    onChange={(e) => setData('receipt_footer', e.target.value)}
                                    placeholder="شكراً لزيارتكم — نتمنى لكم يوماً سعيداً"
                                />
                                <p className="text-xs text-muted-foreground">
                                    يُستخدم كنص افتراضي في قسم التذييل عند إعادة التصميم للافتراضي
                                </p>
                                <InputError message={errors.receipt_footer} />
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="mt-4">
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        <Save className="size-4" />
                        حفظ الإعدادات
                    </Button>
                </div>
            </form>
        </>
    );
}
