import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { MessageCircle, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import CartPanel from '@/components/domain/CartPanel';
import InputError from '@/components/input-error';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import type { CartSummary } from '@/hooks/use-storefront-cart';

type CheckoutSession = {
    verification_id?: number;
    otp_sent?: boolean;
    debug_code?: string | null;
};

type Props = {
    business: StorefrontBusiness;
    navCategories?: NavCategory[];
    cart: CartSummary;
};

export default function CheckoutPage({ business, navCategories = [], cart }: Props) {
    const { t } = useI18n();
    const { checkout } = usePage<{ checkout?: CheckoutSession }>().props;
    const [otpSent, setOtpSent] = useState(Boolean(checkout?.otp_sent));
    const [verificationId, setVerificationId] = useState<number | undefined>(checkout?.verification_id);

    const otpForm = useForm({
        name: '',
        whatsapp: '',
    });

    const confirmForm = useForm({
        verification_id: verificationId ?? 0,
        whatsapp: '',
        otp: '',
    });

    useEffect(() => {
        if (checkout?.otp_sent) {
            setOtpSent(true);
            setVerificationId(checkout.verification_id);
            confirmForm.setData('verification_id', checkout.verification_id ?? 0);
            confirmForm.setData('whatsapp', otpForm.data.whatsapp);

            if (checkout.debug_code) {
                toast.info(t('toast.debug_otp').replace(':code', String(checkout.debug_code)), { duration: 15000 });
            }
        }
    }, [checkout]);

    const sendOtp = (e: React.FormEvent) => {
        e.preventDefault();
        otpForm.post('/checkout/otp', {
            preserveScroll: true,
            onSuccess: () => {
                setOtpSent(true);
                confirmForm.setData('whatsapp', otpForm.data.whatsapp);
            },
        });
    };

    const confirmOrder = (e: React.FormEvent) => {
        e.preventDefault();
        confirmForm.setData({
            verification_id: verificationId ?? confirmForm.data.verification_id,
            whatsapp: otpForm.data.whatsapp,
            otp: confirmForm.data.otp,
        });
        confirmForm.post('/checkout/confirm');
    };

    const panelItems = cart.items.map((item) => ({
        id: item.product_id,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
    }));

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`تأكيد الطلب — ${business.name}`} />

            <div className="mx-auto max-w-6xl px-4 py-10">
                <h1 className="mb-2 text-3xl font-bold text-amber-950">تأكيد الطلب</h1>
                <p className="mb-8 text-muted-foreground">
                    أدخل اسمك ورقم الواتساب. سنرسل لك رمز تحقق لإتمام الطلب.
                </p>

                <div className="grid gap-8 lg:grid-cols-2">
                    <div className="space-y-4">
                        <CartPanel items={panelItems} checkoutLabel="" disabled />

                        {(cart.location_lat && cart.location_lng) && (
                            <Card className="overflow-hidden border-amber-100">
                                <CardContent className="space-y-3 pt-4 text-sm">
                                    <p className="font-semibold">موقع التوصيل</p>
                                    <iframe
                                        title="موقع التوصيل"
                                        className="h-48 w-full rounded-lg border-0"
                                        loading="lazy"
                                        src={`https://www.openstreetmap.org/export/embed.html?bbox=${Number(cart.location_lng) - 0.01}%2C${Number(cart.location_lat) - 0.01}%2C${Number(cart.location_lng) + 0.01}%2C${Number(cart.location_lat) + 0.01}&layer=mapnik&marker=${cart.location_lat}%2C${cart.location_lng}`}
                                    />
                                    {cart.location_address && <p>{cart.location_address}</p>}
                                    {cart.location_notes && (
                                        <p className="text-muted-foreground">{cart.location_notes}</p>
                                    )}
                                    <a
                                        href={`https://www.google.com/maps?q=${cart.location_lat},${cart.location_lng}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-xs text-amber-800 hover:underline"
                                        dir="ltr"
                                    >
                                        فتح في خرائط Google
                                    </a>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <Card className="border-amber-100">
                        <CardContent className="space-y-6 pt-6">
                            {!otpSent ? (
                                <form onSubmit={sendOtp} className="grid gap-4">
                                    <div className="flex items-center gap-2 text-amber-800">
                                        <MessageCircle className="size-5" />
                                        <h2 className="text-lg font-semibold">بيانات التواصل</h2>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">الاسم *</Label>
                                        <Input
                                            id="name"
                                            value={otpForm.data.name}
                                            onChange={(e) => otpForm.setData('name', e.target.value)}
                                            required
                                        />
                                        <InputError message={otpForm.errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="whatsapp">رقم الواتساب *</Label>
                                        <Input
                                            id="whatsapp"
                                            value={otpForm.data.whatsapp}
                                            onChange={(e) => otpForm.setData('whatsapp', e.target.value)}
                                            placeholder="05xxxxxxxx"
                                            dir="ltr"
                                            required
                                        />
                                        <InputError message={otpForm.errors.whatsapp} />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={otpForm.processing}
                                        className="w-full bg-amber-800 hover:bg-amber-900"
                                    >
                                        {otpForm.processing && <Spinner />}
                                        إرسال رمز التحقق
                                    </Button>
                                </form>
                            ) : (
                                <form onSubmit={confirmOrder} className="grid gap-4">
                                    <div className="flex items-center gap-2 text-amber-800">
                                        <ShieldCheck className="size-5" />
                                        <h2 className="text-lg font-semibold">أدخل رمز التحقق</h2>
                                    </div>

                                    <p className="text-sm text-muted-foreground">
                                        تم إرسال رمز مكوّن من 6 أرقام إلى واتساب{' '}
                                        <span dir="ltr">{otpForm.data.whatsapp}</span>
                                    </p>

                                    <div className="flex justify-center">
                                        <InputOTP
                                            maxLength={6}
                                            value={confirmForm.data.otp}
                                            onChange={(value) => confirmForm.setData('otp', value)}
                                        >
                                            <InputOTPGroup>
                                                {[0, 1, 2, 3, 4, 5].map((i) => (
                                                    <InputOTPSlot key={i} index={i} />
                                                ))}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError message={confirmForm.errors.otp} />

                                    <Button
                                        type="submit"
                                        disabled={confirmForm.processing || confirmForm.data.otp.length !== 6}
                                        className="w-full bg-amber-800 hover:bg-amber-900"
                                    >
                                        {confirmForm.processing && <Spinner />}
                                        تأكيد الطلب
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setOtpSent(false);
                                            confirmForm.reset('otp');
                                        }}
                                    >
                                        تغيير الرقم أو إعادة الإرسال
                                    </Button>
                                </form>
                            )}

                            <Button variant="outline" asChild>
                                <Link href="/cart">← العودة للسلة</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </StorefrontLayout>
    );
}
