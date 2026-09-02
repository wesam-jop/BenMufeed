import { Head, Link, router, useForm } from '@inertiajs/react';
import { Clock, MapPin, MessageCircle, ShoppingBag, User } from 'lucide-react';
import { useEffect } from 'react';
import CartPanel from '@/components/domain/CartPanel';
import DeliveryLocationMap from '@/components/domain/DeliveryLocationMap';
import InputError from '@/components/input-error';
import StorefrontPageRenderer from '@/components/storefront/page-renderer/StorefrontPageRenderer';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useStorefrontCart, type CartSummary } from '@/hooks/use-storefront-cart';
import { cn } from '@/lib/utils';
import type { CmsPageTheme, CmsSection } from '@/types/cms-page';

type GuestProfile = {
    name: string;
    whatsapp: string;
    phone: string;
};

type DeliveryHours = {
    is_open: boolean;
    opens_at?: string;
    closes_at?: string;
    opens_at_label?: string;
    closes_at_label?: string;
    message?: string;
};

type Props = {
    business: StorefrontBusiness & {
        delivery_hours?: DeliveryHours | null;
        opening_hours?: string | null;
    };
    navCategories?: NavCategory[];
    cart: CartSummary;
    step: number;
    guestProfile?: GuestProfile | null;
    cmsPage?: {
        title: string;
        seo_title?: string | null;
        theme: CmsPageTheme;
        sections: CmsSection[];
    };
};

const STEPS = [
    { id: 1, label: 'السلة', icon: ShoppingBag },
    { id: 2, label: 'الموقع', icon: MapPin },
    { id: 3, label: 'التأكيد', icon: User },
];

export default function CartPage({
    business,
    navCategories = [],
    cart,
    step,
    guestProfile = null,
    cmsPage,
}: Props) {
    const { updateQuantity, removeItem } = useStorefrontCart();
    const deliveryHours = business.delivery_hours;
    const deliveryOpen = deliveryHours?.is_open !== false;

    const locationForm = useForm({
        location_lat: cart.location_lat ?? '',
        location_lng: cart.location_lng ?? '',
        location_address: cart.location_address ?? '',
        location_notes: cart.location_notes ?? '',
        next_step: 3,
    });

    const contactForm = useForm({
        name: guestProfile?.name ?? '',
        whatsapp: guestProfile?.whatsapp ?? '',
    });

    useEffect(() => {
        locationForm.setData({
            location_lat: cart.location_lat ?? '',
            location_lng: cart.location_lng ?? '',
            location_address: cart.location_address ?? '',
            location_notes: cart.location_notes ?? '',
            next_step: 3,
        });
    }, [cart]);

    useEffect(() => {
        if (guestProfile) {
            contactForm.setData({
                name: guestProfile.name,
                whatsapp: guestProfile.whatsapp,
            });
        }
    }, [guestProfile]);

    const panelItems = cart.items.map((item) => ({
        id: item.product_id,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
    }));

    const hasMapLocation = Boolean(locationForm.data.location_lat) && Boolean(locationForm.data.location_lng);
    const hasSavedProfile = Boolean(guestProfile?.name && guestProfile?.whatsapp);

    const goToStep = (nextStep: number) => {
        router.visit(`/cart?step=${nextStep}`, { preserveScroll: true });
    };

    const saveLocation = (e: React.FormEvent) => {
        e.preventDefault();
        locationForm.patch('/cart/location');
    };

    const handleMapLocation = (lat: number, lng: number, address?: string) => {
        locationForm.setData((current) => ({
            ...current,
            location_lat: lat.toFixed(7),
            location_lng: lng.toFixed(7),
            location_address: address ?? current.location_address,
        }));
    };

    const sendOtp = (e?: React.FormEvent) => {
        e?.preventDefault();
        if (!deliveryOpen) {
            return;
        }
        contactForm.post('/checkout/otp');
    };

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`${cmsPage?.seo_title || cmsPage?.title || 'السلة'} — ${business.name}`} />

            {cmsPage ? (
                <div className="mx-auto max-w-6xl px-4 pt-4 md:px-6">
                    <StorefrontPageRenderer
                        theme={cmsPage.theme}
                        sections={cmsPage.sections}
                        business={business}
                    />
                </div>
            ) : null}

            <div className="mx-auto max-w-3xl px-4 py-10">
                {!cmsPage && (
                    <h1 className="mb-8 text-3xl font-bold text-amber-950">سلة المشتريات</h1>
                )}

                {!deliveryOpen && (
                    <div className="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-rose-900">
                        <Clock className="mt-0.5 size-5 shrink-0" />
                        <div>
                            <p className="font-semibold">المقهى مغلق لطلبات التوصيل حالياً</p>
                            <p className="mt-1 text-sm text-rose-800/90">
                                {deliveryHours?.message ??
                                    `يمكنك التصفح الآن، والتوصيل متاح من ${deliveryHours?.opens_at_label ?? 'وقت الفتح'} إلى ${deliveryHours?.closes_at_label ?? 'وقت الإغلاق'}.`}
                            </p>
                        </div>
                    </div>
                )}

                <nav className="mb-8 flex items-center justify-center gap-2">
                    {STEPS.map((s, index) => {
                        const Icon = s.icon;
                        const isActive = step === s.id;
                        const isDone = step > s.id;

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

                {step === 1 && (
                    <div className="space-y-6">
                        <CartPanel
                            items={panelItems}
                            onIncrease={(id) => {
                                const item = cart.items.find((i) => i.product_id === id);
                                if (item) updateQuantity(Number(id), item.quantity + 1);
                            }}
                            onDecrease={(id) => {
                                const item = cart.items.find((i) => i.product_id === id);
                                if (item) updateQuantity(Number(id), Math.max(0, item.quantity - 1));
                            }}
                            onRemove={(id) => removeItem(Number(id))}
                            checkoutLabel="متابعة"
                            onCheckout={() => goToStep(2)}
                            disabled={cart.count === 0}
                        />

                        <Button variant="ghost" asChild>
                            <Link href="/products">← متابعة التسوق</Link>
                        </Button>
                    </div>
                )}

                {step === 2 && (
                    <Card className="border-amber-100">
                        <CardContent className="space-y-4 pt-6">
                            <div className="flex items-center gap-2">
                                <MapPin className="size-5 text-amber-700" />
                                <h2 className="text-xl font-semibold">موقع التوصيل على الخريطة</h2>
                            </div>

                            {hasSavedProfile && (
                                <p className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                    مرحباً {guestProfile?.name}! يمكنك تغيير موقع التوصيل فقط.
                                </p>
                            )}

                            <form onSubmit={saveLocation} className="grid gap-4">
                                <DeliveryLocationMap
                                    lat={locationForm.data.location_lat}
                                    lng={locationForm.data.location_lng}
                                    onLocationChange={handleMapLocation}
                                />

                                {hasMapLocation && (
                                    <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900" dir="ltr">
                                        {Number(locationForm.data.location_lat).toFixed(6)},{' '}
                                        {Number(locationForm.data.location_lng).toFixed(6)}
                                    </p>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="location_address">العنوان</Label>
                                    <Input
                                        id="location_address"
                                        value={locationForm.data.location_address}
                                        onChange={(e) => locationForm.setData('location_address', e.target.value)}
                                        placeholder="يُملأ تلقائياً من الخريطة — يمكنك تعديله"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="location_notes">شرح إضافي عن الموقع</Label>
                                    <textarea
                                        id="location_notes"
                                        rows={3}
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        value={locationForm.data.location_notes}
                                        onChange={(e) => locationForm.setData('location_notes', e.target.value)}
                                        placeholder="مثال: بجانب مسجد... الطابق الثاني... رقم الشقة..."
                                    />
                                </div>

                                <div className="flex flex-wrap gap-3">
                                    <Button type="button" variant="outline" onClick={() => goToStep(1)}>
                                        ← رجوع
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={locationForm.processing || !hasMapLocation}
                                        className="flex-1 bg-amber-800 hover:bg-amber-900"
                                    >
                                        {locationForm.processing && <Spinner />}
                                        متابعة
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {step === 3 && (
                    <div className="space-y-6">
                        <CartPanel items={panelItems} checkoutLabel="" disabled />

                        {(cart.location_lat && cart.location_lng) && (
                            <Card className="overflow-hidden border-amber-100">
                                <CardContent className="space-y-3 pt-4 text-sm">
                                    <div className="flex items-center justify-between">
                                        <p className="font-semibold">موقع التوصيل</p>
                                        <Button variant="link" size="sm" className="h-auto p-0" onClick={() => goToStep(2)}>
                                            تغيير الموقع
                                        </Button>
                                    </div>
                                    {cart.location_address && <p>{cart.location_address}</p>}
                                    {cart.location_notes && (
                                        <p className="text-muted-foreground">{cart.location_notes}</p>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <Card className="border-amber-100">
                            <CardContent className="space-y-6 pt-6">
                                <div className="flex items-center gap-2 text-amber-800">
                                    <MessageCircle className="size-5" />
                                    <h2 className="text-lg font-semibold">
                                        {hasSavedProfile ? 'تأكيد الطلب' : 'بيانات التواصل'}
                                    </h2>
                                </div>

                                {hasSavedProfile ? (
                                    <div className="space-y-2 rounded-lg bg-amber-50 p-4 text-sm">
                                        <p>
                                            <span className="text-muted-foreground">الاسم: </span>
                                            <span className="font-medium">{guestProfile?.name}</span>
                                        </p>
                                        <p>
                                            <span className="text-muted-foreground">واتساب: </span>
                                            <span className="font-medium" dir="ltr">
                                                {guestProfile?.whatsapp}
                                            </span>
                                        </p>
                                    </div>
                                ) : (
                                    <form id="contact-form" onSubmit={sendOtp} className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">الاسم *</Label>
                                            <Input
                                                id="name"
                                                value={contactForm.data.name}
                                                onChange={(e) => contactForm.setData('name', e.target.value)}
                                                required
                                            />
                                            <InputError message={contactForm.errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="whatsapp">رقم الواتساب *</Label>
                                            <Input
                                                id="whatsapp"
                                                value={contactForm.data.whatsapp}
                                                onChange={(e) => contactForm.setData('whatsapp', e.target.value)}
                                                placeholder="05xxxxxxxx"
                                                dir="ltr"
                                                required
                                            />
                                            <InputError message={contactForm.errors.whatsapp} />
                                        </div>
                                    </form>
                                )}

                                {!deliveryOpen && (
                                    <div className="rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-800">
                                        لا يمكن إتمام طلب التوصيل خارج أوقات العمل.
                                    </div>
                                )}

                                <div className="flex flex-wrap gap-3">
                                    <Button type="button" variant="outline" onClick={() => goToStep(2)}>
                                        ← رجوع
                                    </Button>
                                    <Button
                                        type={hasSavedProfile ? 'button' : 'submit'}
                                        form={hasSavedProfile ? undefined : 'contact-form'}
                                        onClick={hasSavedProfile ? () => sendOtp() : undefined}
                                        disabled={contactForm.processing || !deliveryOpen}
                                        className="flex-1 bg-amber-800 hover:bg-amber-900"
                                    >
                                        {contactForm.processing && <Spinner />}
                                        {deliveryOpen ? 'تم الطلب' : 'التوصيل مغلق الآن'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
