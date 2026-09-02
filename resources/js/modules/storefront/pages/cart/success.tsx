import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import StorefrontPageRenderer from '@/components/storefront/page-renderer/StorefrontPageRenderer';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency } from '@/lib/format';
import type { CmsPageTheme, CmsSection } from '@/types/cms-page';

type OrderItem = {
    id: number;
    quantity: number;
    unit_price: number | string;
    product?: { name: string } | null;
};

type Order = {
    id: number;
    order_number: string;
    total: number | string;
    delivery_address?: string | null;
    items: OrderItem[];
};

type Props = {
    business: StorefrontBusiness & Record<string, unknown>;
    navCategories?: NavCategory[];
    order: Order;
    cmsPage?: {
        title: string;
        seo_title?: string | null;
        theme: CmsPageTheme;
        sections: CmsSection[];
    };
};

export default function CheckoutSuccessPage({
    business,
    navCategories = [],
    order,
    cmsPage,
}: Props) {
    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`${cmsPage?.seo_title || 'تم الطلب'} — ${business.name}`} />

            {cmsPage ? (
                <div className="mx-auto max-w-6xl px-4 pt-4 md:px-6">
                    <StorefrontPageRenderer
                        theme={cmsPage.theme}
                        sections={cmsPage.sections}
                        business={business}
                    />
                </div>
            ) : null}

            <div className="mx-auto max-w-2xl px-4 py-10 text-center">
                {!cmsPage && (
                    <>
                        <CheckCircle2 className="mx-auto size-16 text-green-600" />
                        <h1 className="mt-4 text-3xl font-bold text-amber-950">تم تأكيد طلبك!</h1>
                    </>
                )}
                <p className="mt-2 text-muted-foreground">
                    رقم الطلب: <span className="font-semibold">{order.order_number}</span>
                </p>

                <Card className="mt-8 border-amber-100 text-start">
                    <CardContent className="space-y-3 pt-6">
                        {order.items.map((item) => (
                            <div key={item.id} className="flex justify-between text-sm">
                                <span>
                                    {item.product?.name} × {item.quantity}
                                </span>
                                <span>
                                    {formatCurrency(Number(item.unit_price) * Number(item.quantity))}
                                </span>
                            </div>
                        ))}
                        <div className="flex justify-between border-t pt-3 font-bold">
                            <span>الإجمالي</span>
                            <span>{formatCurrency(Number(order.total))}</span>
                        </div>
                        {order.delivery_address && (
                            <p className="whitespace-pre-line border-t pt-3 text-sm text-muted-foreground">
                                {order.delivery_address}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <Button asChild className="bg-amber-800 hover:bg-amber-900">
                        <Link href="/customer">حسابي</Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href="/">العودة للرئيسية</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/products">تصفّح المزيد</Link>
                    </Button>
                </div>
                <p className="mt-4 text-sm text-muted-foreground">
                    تم تسجيل دخولك تلقائياً — يمكنك الطلب مرة أخرى بدون إدخال بياناتك.
                </p>
            </div>
        </StorefrontLayout>
    );
}
