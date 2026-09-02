import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import SalesRowActions from '@/components/admin/sales-row-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatCurrency, formatDate, invoiceStatusVariant, statusLabel } from '@/lib/format';

type InvoiceItem = {
    product_name: string;
    quantity: number;
    unit_price: number;
    total: number;
};

type Invoice = {
    id: number;
    invoice_number?: string | null;
    status: string;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    paid: number;
    created_at?: string | null;
    cashier_name?: string | null;
    order_number?: string | null;
    items: InvoiceItem[];
};

type Props = {
    invoice: Invoice;
};

export default function InvoicesShow({ invoice }: Props) {

    const isVoid = invoice.status === 'void';

    return (
        <>
            <Head title={`فاتورة ${invoice.invoice_number ?? invoice.id}`} />
            <PageHeader
                title={`فاتورة #${invoice.invoice_number ?? invoice.id}`}
                description={invoice.order_number ? `مرتبطة بالطلب ${invoice.order_number}` : 'تفاصيل الفاتورة'}
                actions={
                    <div className="flex items-center gap-2">
                        <SalesRowActions
                            editHref={!isVoid ? `/pos/invoices/${invoice.id}/edit` : undefined}
                            printHref={`/pos/invoices/${invoice.id}/print`}
                            deleteHref={!isVoid ? `/pos/invoices/${invoice.id}` : undefined}
                            editPermission="sales.update"
                            printPermission="sales.print_invoice"
                            deletePermission="sales.cancel"
                        />
                        <Button variant="outline" asChild>
                            <Link href="/admin/invoices">
                                <ArrowRight className="size-4" />
                                رجوع
                            </Link>
                        </Button>
                    </div>
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>أصناف الفاتورة</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {invoice.items.map((item, index) => (
                            <div
                                key={index}
                                className="flex items-center justify-between rounded-lg border px-3 py-2"
                            >
                                <div>
                                    <p className="font-medium">{item.product_name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {item.quantity} × {formatCurrency(item.unit_price)}
                                    </p>
                                </div>
                                <p className="font-semibold">{formatCurrency(item.total)}</p>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>ملخص</CardTitle>
                        <Badge variant={invoiceStatusVariant(invoice.status)}>
                            {statusLabel(invoice.status)}
                        </Badge>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">التاريخ</span>
                            <span>{invoice.created_at ? formatDate(invoice.created_at) : '—'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">الكاشير</span>
                            <span>{invoice.cashier_name ?? '—'}</span>
                        </div>
                        <Separator className="my-3" />
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">المجموع الفرعي</span>
                            <span>{formatCurrency(invoice.subtotal)}</span>
                        </div>
                        {invoice.discount > 0 && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">الخصم</span>
                                <span>-{formatCurrency(invoice.discount)}</span>
                            </div>
                        )}
                        {invoice.tax > 0 && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">الضريبة</span>
                                <span>{formatCurrency(invoice.tax)}</span>
                            </div>
                        )}
                        <div className="flex justify-between text-lg font-bold">
                            <span>الإجمالي</span>
                            <span className="text-emerald-600">{formatCurrency(invoice.total)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">المدفوع</span>
                            <span>{formatCurrency(invoice.paid)}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
