import { Head, Link, useForm } from '@inertiajs/react';
import { MapPin, ShieldCheck, ShoppingBag, User } from 'lucide-react';
import InputError from '@/components/input-error';
import CartPanel from '@/components/domain/CartPanel';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import type { CartSummary } from '@/hooks/use-storefront-cart';
import { cn } from '@/lib/utils';

type Props = {
    business: StorefrontBusiness;
    navCategories?: NavCategory[];
    cart: CartSummary;
    verificationId: number;
    whatsapp: string;
    customerName?: string | null;
};

const STEPS = [
    { id: 1, label: 'السلة', icon: ShoppingBag },
    { id: 2, label: 'الموقع', icon: MapPin },
    { id: 3, label: 'التأكيد', icon: User },
    { id: 4, label: 'رمز التحقق', icon: ShieldCheck },
];

export default function CheckoutVerifyPage({
    business,
    navCategories = [],
    cart,
    verificationId,
    whatsapp,
    customerName,
}: Props) {

    const form = useForm({
        verification_id: verificationId,
        whatsapp,
        otp: '',
    });

    const resendForm = useForm({
        name: customerName ?? '',
        whatsapp,
    });

    const panelItems = cart.items.map((item) => ({
        id: item.product_id,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
    }));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/checkout/confirm');
    };

    const resendOtp = () => {
        resendForm.post('/checkout/otp', { preserveScroll: true });
    };

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`رمز التحقق — ${business.name}`} />

            <div className="mx-auto max-w-3xl px-4 py-10">
                <h1 className="mb-8 text-3xl font-bold text-amber-950">تأكيد الطلب</h1>

                <nav className="mb-8 flex flex-wrap items-center justify-center gap-2">
                    {STEPS.map((s, index) => {
                        const Icon = s.icon;
                        const isActive = s.id === 4;
                        const isDone = s.id < 4;

                        return (
                            <div key={s.id} className="flex items-center gap-2">
                                <div
                                    className={cn(
                                        'flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                                        isActive && 'bg-amber-800 text-white',
                                        isDone && 'bg-amber-100 text-amber-900',
                                        !isActive && !isDone && 'bg-muted text-muted-foreground',
                                    )}
                                >
                                    <Icon className="size-4" />
                                    <span>{s.label}</span>
                                </div>
                                {index < STEPS.length - 1 && (
                                    <div className={cn('h-px w-8', isDone ? 'bg-amber-400' : 'bg-border')} />
                                )}
                            </div>
                        );
                    })}
                </nav>

                <div className="grid gap-6">
                    <CartPanel items={panelItems} checkoutLabel="" disabled />

                    <Card className="border-amber-100">
                        <CardContent className="space-y-6 pt-6">
                            <div className="flex items-center gap-2 text-amber-800">
                                <ShieldCheck className="size-5" />
                                <h2 className="text-lg font-semibold">أدخل رمز التحقق</h2>
                            </div>

                            <p className="text-sm text-muted-foreground">
                                أدخل الرمز المكوّن من 6 أرقام
                                {customerName ? (
                                    <>
                                        {' '}
                                        لـ <span className="font-medium text-foreground">{customerName}</span>
                                    </>
                                ) : null}
                                {' — '}
                                <span dir="ltr" className="font-medium text-foreground">
                                    {whatsapp}
                                </span>
                            </p>

                            <form onSubmit={submit} className="grid gap-4">
                                <div className="flex justify-center py-2">
                                    <InputOTP
                                        maxLength={6}
                                        value={form.data.otp}
                                        onChange={(value) => form.setData('otp', value)}
                                    >
                                        <InputOTPGroup>
                                            {[0, 1, 2, 3, 4, 5].map((i) => (
                                                <InputOTPSlot key={i} index={i} />
                                            ))}
                                        </InputOTPGroup>
                                    </InputOTP>
                                </div>
                                <InputError message={form.errors.otp} />

                                <Button
                                    type="submit"
                                    disabled={form.processing || form.data.otp.length !== 6}
                                    className="w-full bg-amber-800 hover:bg-amber-900"
                                >
                                    {form.processing && <Spinner />}
                                    تأكيد الطلب
                                </Button>
                            </form>

                            <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/cart?step=3">← رجوع</Link>
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={resendOtp}
                                    disabled={resendForm.processing}
                                >
                                    {resendForm.processing && <Spinner />}
                                    إعادة إرسال الرمز
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </StorefrontLayout>
    );
}
