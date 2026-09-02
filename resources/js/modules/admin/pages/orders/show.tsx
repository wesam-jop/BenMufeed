import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Mail,
    MapPin,
    MessageCircle,
    Navigation,
    Phone,
    Star,
    User,
} from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import SalesRowActions from '@/components/admin/sales-row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    formatCurrency,
    formatDate,
    invoiceStatusVariant,
    orderSourceLabel,
    orderStatusLabel,
    orderStatusVariant,
    statusLabel,
} from '@/lib/format';

type OrderItem = {
    id: number;
    name: string;
    quantity: number;
    unit_price: number;
    total: number;
};

type InvoiceRow = {
    id: number;
    invoice_number?: string | null;
    status: string;
    total: number;
    paid: number;
    created_at?: string | null;
};

type CustomerAddress = {
    id: number;
    label?: string | null;
    formatted: string;
    city?: string | null;
    district?: string | null;
    street?: string | null;
    building?: string | null;
    floor?: string | null;
    apartment?: string | null;
    notes?: string | null;
    lat?: number | null;
    lng?: number | null;
    is_default?: boolean;
    maps_url?: string | null;
};

type Customer = {
    id: number;
    name: string;
    phone?: string | null;
    whatsapp?: string | null;
    email?: string | null;
    loyalty_points?: number;
    addresses?: CustomerAddress[];
};

type DeliveryInfo = {
    raw?: string | null;
    address?: string | null;
    lat?: number | null;
    lng?: number | null;
    location_notes?: string | null;
    maps_url?: string | null;
};

type Order = {
    id: number;
    order_number?: string | null;
    type: string;
    status: string;
    source?: string | null;
    total: number;
    subtotal: number;
    tax_amount: number;
    discount_amount: number;
    delivery_fee: number;
    notes?: string | null;
    rejection_reason?: string | null;
    delivery_address?: string | null;
    delivery?: DeliveryInfo | null;
    created_at?: string | null;
    cashier_name?: string | null;
    customer?: Customer | null;
    items: OrderItem[];
    invoices: InvoiceRow[];
    invoice?: InvoiceRow | null;
};

type Props = {
    order: Order;
};

function phoneHref(value?: string | null): string | null {
    if (!value) return null;
    const digits = value.replace(/\D/g, '');
    return digits ? `tel:${digits}` : null;
}

function whatsappHref(value?: string | null): string | null {
    if (!value) return null;
    let digits = value.replace(/\D/g, '');
    if (digits.startsWith('0')) digits = `966${digits.slice(1)}`;
    if (!digits.startsWith('966') && digits.length <= 10) digits = `966${digits}`;
    return digits ? `https://wa.me/${digits}` : null;
}

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 text-sm">
            <span className="shrink-0 text-muted-foreground">{label}</span>
            <span className="text-left font-medium">{value}</span>
        </div>
    );
}

export default function OrdersShow({ order }: Props) {

    const activeInvoice = order.invoices.find((invoice) => invoice.status !== 'void') ?? order.invoice;
    const customer = order.customer;
    const delivery = order.delivery;
    const tel = phoneHref(customer?.phone ?? customer?.whatsapp);
    const wa = whatsappHref(customer?.whatsapp ?? customer?.phone);

    return (
        <>
            <Head title={`طلب توصيل ${order.order_number ?? order.id}`} />
            <PageHeader
                title={`طلب توصيل ${order.order_number ?? order.id}`}
                description="تفاصيل العميل والموقع والطلب"
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/orders">
                            <ArrowRight className="size-4" />
                            رجوع لطلبات التوصيل
                        </Link>
                    </Button>
                }
            />

            <div className="grid gap-6 xl:grid-cols-3">
                <div className="space-y-6 xl:col-span-2">
                    {customer && (
                        <Card className="overflow-hidden border-emerald-200/60">
                            <CardHeader className="border-b bg-emerald-50/50 pb-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                            <User className="size-6" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-xl">{customer.name}</CardTitle>
                                            <p className="text-sm text-muted-foreground">صاحب الطلب</p>
                                        </div>
                                    </div>
                                    {customer.loyalty_points != null && customer.loyalty_points > 0 && (
                                        <Badge variant="secondary" className="gap-1">
                                            <Star className="size-3.5" />
                                            {customer.loyalty_points} نقطة
                                        </Badge>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="grid gap-4 pt-5 sm:grid-cols-2">
                                <div className="space-y-3">
                                    <InfoRow
                                        label="الجوال"
                                        value={
                                            customer.phone ? (
                                                <a href={tel ?? '#'} dir="ltr" className="text-emerald-700 hover:underline">
                                                    {customer.phone}
                                                </a>
                                            ) : (
                                                '—'
                                            )
                                        }
                                    />
                                    <InfoRow
                                        label="واتساب"
                                        value={
                                            customer.whatsapp ? (
                                                <a
                                                    href={wa ?? '#'}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    dir="ltr"
                                                    className="inline-flex items-center gap-1 text-emerald-700 hover:underline"
                                                >
                                                    <MessageCircle className="size-3.5" />
                                                    {customer.whatsapp}
                                                </a>
                                            ) : (
                                                '—'
                                            )
                                        }
                                    />
                                    <InfoRow
                                        label="البريد"
                                        value={
                                            customer.email ? (
                                                <a
                                                    href={`mailto:${customer.email}`}
                                                    className="inline-flex items-center gap-1 text-emerald-700 hover:underline"
                                                >
                                                    <Mail className="size-3.5" />
                                                    {customer.email}
                                                </a>
                                            ) : (
                                                '—'
                                            )
                                        }
                                    />
                                </div>

                                <div className="flex flex-wrap gap-2 sm:justify-end sm:pt-1">
                                    {tel && (
                                        <Button variant="outline" size="sm" asChild>
                                            <a href={tel}>
                                                <Phone className="size-4" />
                                                اتصال
                                            </a>
                                        </Button>
                                    )}
                                    {wa && (
                                        <Button
                                            size="sm"
                                            className="bg-emerald-600 hover:bg-emerald-700"
                                            asChild
                                        >
                                            <a href={wa} target="_blank" rel="noopener noreferrer">
                                                <MessageCircle className="size-4" />
                                                واتساب
                                            </a>
                                        </Button>
                                    )}
                                    {delivery?.maps_url && (
                                        <Button variant="secondary" size="sm" asChild>
                                            <a href={delivery.maps_url} target="_blank" rel="noopener noreferrer">
                                                <Navigation className="size-4" />
                                                فتح الخريطة
                                            </a>
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="size-5 text-emerald-600" />
                                موقع التوصيل
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {delivery?.address ? (
                                <div className="rounded-xl border bg-slate-50 p-4">
                                    <p className="mb-1 text-xs font-medium text-muted-foreground">العنوان</p>
                                    <p className="leading-relaxed">{delivery.address}</p>
                                </div>
                            ) : order.delivery_address ? (
                                <div className="rounded-xl border bg-slate-50 p-4">
                                    <p className="leading-relaxed whitespace-pre-wrap">{order.delivery_address}</p>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">لا يوجد عنوان مسجّل</p>
                            )}

                            {(delivery?.lat != null && delivery?.lng != null) && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <InfoRow label="خط العرض" value={<span dir="ltr">{delivery.lat}</span>} />
                                    <InfoRow label="خط الطول" value={<span dir="ltr">{delivery.lng}</span>} />
                                </div>
                            )}

                            {delivery?.location_notes && (
                                <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                                    <p className="mb-1 font-medium">ملاحظات الموقع</p>
                                    <p>{delivery.location_notes}</p>
                                </div>
                            )}

                            {order.notes && (
                                <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-950">
                                    <p className="mb-1 font-medium">ملاحظات الطلب</p>
                                    <p>{order.notes}</p>
                                </div>
                            )}

                            {customer?.addresses && customer.addresses.length > 0 && (
                                <>
                                    <Separator />
                                    <div>
                                        <p className="mb-3 text-sm font-medium">عناوين العميل المحفوظة</p>
                                        <div className="space-y-2">
                                            {customer.addresses.map((address) => (
                                                <div
                                                    key={address.id}
                                                    className="flex items-start justify-between gap-3 rounded-lg border px-3 py-2 text-sm"
                                                >
                                                    <div>
                                                        <div className="mb-1 flex items-center gap-2">
                                                            {address.label && (
                                                                <span className="font-medium">{address.label}</span>
                                                            )}
                                                            {address.is_default && (
                                                                <Badge variant="outline" className="text-xs">
                                                                    افتراضي
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <p className="text-muted-foreground">{address.formatted}</p>
                                                    </div>
                                                    {address.maps_url && (
                                                        <Button variant="ghost" size="sm" asChild>
                                                            <a
                                                                href={address.maps_url}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                            >
                                                                <MapPin className="size-4" />
                                                            </a>
                                                        </Button>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>أصناف الطلب</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {order.items.length === 0 ? (
                                <p className="text-sm text-muted-foreground">لا توجد أصناف</p>
                            ) : (
                                order.items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between rounded-lg border px-3 py-2"
                                    >
                                        <div>
                                            <p className="font-medium">{item.name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {item.quantity} × {formatCurrency(item.unit_price)}
                                            </p>
                                        </div>
                                        <p className="font-semibold">{formatCurrency(item.total)}</p>
                                    </div>
                                ))
                            )}
                            <Separator />
                            <div className="space-y-1.5 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">المجموع الفرعي</span>
                                    <span>{formatCurrency(order.subtotal)}</span>
                                </div>
                                {order.discount_amount > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">الخصم</span>
                                        <span>-{formatCurrency(order.discount_amount)}</span>
                                    </div>
                                )}
                                {order.tax_amount > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">الضريبة</span>
                                        <span>{formatCurrency(order.tax_amount)}</span>
                                    </div>
                                )}
                                {order.delivery_fee > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">رسوم التوصيل</span>
                                        <span>{formatCurrency(order.delivery_fee)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between pt-1 text-lg font-bold">
                                    <span>الإجمالي</span>
                                    <span className="text-emerald-600">{formatCurrency(order.total)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>معلومات الطلب</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <InfoRow
                                label="الحالة"
                                value={
                                    <Badge variant={orderStatusVariant(order.status)}>
                                        {orderStatusLabel(order.status)}
                                    </Badge>
                                }
                            />
                            <InfoRow
                                label="المصدر"
                                value={order.source ? orderSourceLabel(order.source) : '—'}
                            />
                            <InfoRow
                                label="التاريخ"
                                value={order.created_at ? formatDate(order.created_at) : '—'}
                            />
                            <InfoRow label="الكاشير" value={order.cashier_name ?? '—'} />

                            {order.status === 'rejected' && order.rejection_reason && (
                                <div className="rounded-xl border border-red-200 bg-red-50 p-3 text-red-900">
                                    <p className="mb-1 font-medium">سبب الرفض</p>
                                    <p>{order.rejection_reason}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {activeInvoice && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>الفاتورة</CardTitle>
                                <Badge variant={invoiceStatusVariant(activeInvoice.status)}>
                                    {statusLabel(activeInvoice.status)}
                                </Badge>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <InfoRow
                                    label="رقم الفاتورة"
                                    value={`#${activeInvoice.invoice_number}`}
                                />
                                <InfoRow
                                    label="المبلغ"
                                    value={
                                        <span className="font-bold text-emerald-600">
                                            {formatCurrency(activeInvoice.total)}
                                        </span>
                                    }
                                />
                                <SalesRowActions
                                    editHref={`/pos/invoices/${activeInvoice.id}/edit`}
                                    printHref={`/pos/invoices/${activeInvoice.id}/print`}
                                    deleteHref={`/pos/invoices/${activeInvoice.id}`}
                                    editPermission="sales.update"
                                    printPermission="sales.print_invoice"
                                    deletePermission="sales.cancel"
                                />
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}
