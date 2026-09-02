import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    Clock,
    Pencil,
    Printer,
    Receipt,
    Trash2,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import BusinessBrand from '@/components/domain/BusinessBrand';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency, formatDate, invoiceStatusVariant, statusLabel } from '@/lib/format';
import { cn } from '@/lib/utils';

type SessionSummary = {
    id: number;
    session_date: string;
    status: string;
    register_name?: string | null;
    expected_cash: number;
    invoice_count: number;
    total_sales: number;
    cash_sales: number;
    card_sales: number;
};

type InvoiceRow = {
    id: number;
    invoice_number?: string | null;
    total: number;
    paid: number;
    status?: string | null;
    items_count?: number;
    payment_method?: string | null;
    cashier_name?: string | null;
    created_at?: string | null;
};

type Props = {
    session: SessionSummary;
    invoices?: InvoiceRow[];
};

function StatCard({
    label,
    value,
    icon: Icon,
    accent,
}: {
    label: string;
    value: string;
    icon: typeof Receipt;
    accent: string;
}) {
    return (
        <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <span className="text-sm font-medium text-slate-500">{label}</span>
                <div className={cn('flex size-10 items-center justify-center rounded-xl', accent)}>
                    <Icon className="size-5" />
                </div>
            </div>
            <p className="text-2xl font-bold tracking-tight text-slate-900">{value}</p>
        </div>
    );
}

export default function CloseSession({ session, invoices = [] }: Props) {

    const [deleteTarget, setDeleteTarget] = useState<InvoiceRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const handlePrint = (invoiceId: number) => {
        window.open(`/pos/invoices/${invoiceId}/print`, '_blank', 'noopener,noreferrer');
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;

        setDeleting(true);
        router.delete(`/pos/invoices/${deleteTarget.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <div className="min-h-screen bg-slate-100" dir="rtl">
            <Head title={`ملخص يوم ${session.session_date}`} />

            <header className="border-b border-slate-800 bg-slate-900 text-white shadow-lg">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 py-4">
                    <BusinessBrand
                        iconWrapperClassName="bg-white/10"
                        textClassName="text-white [&_span]:text-white"
                    />

                    <div className="min-w-0 flex-1">
                        <h1 className="text-xl font-bold">ملخص يوم {session.session_date}</h1>
                        <p className="text-sm text-slate-400">
                            {session.register_name ?? 'الصندوق'} — فواتير هذا اليوم
                        </p>
                    </div>

                    <Button
                        variant="secondary"
                        size="sm"
                        className="gap-2 bg-white/10 text-white hover:bg-white/20"
                        asChild
                    >
                        <Link href="/pos">
                            <ArrowRight className="size-4" />
                            رجوع للكاشير
                        </Link>
                    </Button>
                </div>
            </header>

            <main className="mx-auto max-w-6xl space-y-6 p-4 pb-10">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="عدد الفواتير"
                        value={String(session.invoice_count)}
                        icon={Receipt}
                        accent="bg-blue-50 text-blue-600"
                    />
                    <StatCard
                        label="إجمالي المبيعات"
                        value={formatCurrency(session.total_sales)}
                        icon={TrendingUp}
                        accent="bg-emerald-50 text-emerald-600"
                    />
                    <StatCard
                        label="مبيعات نقدية"
                        value={formatCurrency(session.cash_sales)}
                        icon={Banknote}
                        accent="bg-amber-50 text-amber-600"
                    />
                    <StatCard
                        label="النقد في الصندوق"
                        value={formatCurrency(session.expected_cash)}
                        icon={Wallet}
                        accent="bg-violet-50 text-violet-600"
                    />
                </div>

                <section className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 className="text-lg font-bold text-slate-900">فواتير اليوم</h2>
                            <p className="text-sm text-slate-500">{invoices.length} فاتورة مسجّلة</p>
                        </div>
                    </div>

                    {invoices.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-20 text-slate-400">
                            <Receipt className="mb-3 size-12 opacity-40" />
                            <p className="font-medium">لا توجد فواتير لهذا اليوم بعد</p>
                            <p className="mt-1 text-sm">ستظهر الفواتير هنا بعد إتمام المبيعات</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-slate-100">
                            {invoices.map((invoice) => {
                                const isVoid = invoice.status === 'void';

                                return (
                                    <div
                                        key={invoice.id}
                                        className={cn(
                                            'flex flex-col gap-4 px-5 py-4 transition-colors hover:bg-slate-50/80 lg:flex-row lg:items-center',
                                            isVoid && 'opacity-60',
                                        )}
                                    >
                                        <div className="flex min-w-0 flex-1 items-start gap-4">
                                            <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                                <Receipt className="size-5" />
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <div className="mb-1 flex flex-wrap items-center gap-2">
                                                    <p className="font-bold text-slate-900">
                                                        #{invoice.invoice_number ?? invoice.id}
                                                    </p>
                                                    {invoice.status && (
                                                        <Badge variant={invoiceStatusVariant(invoice.status)}>
                                                            {statusLabel(invoice.status)}
                                                        </Badge>
                                                    )}
                                                </div>

                                                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                                    {invoice.created_at && (
                                                        <span className="inline-flex items-center gap-1">
                                                            <Clock className="size-3.5" />
                                                            {formatDate(invoice.created_at)}
                                                        </span>
                                                    )}
                                                    {invoice.items_count != null && (
                                                        <span>{invoice.items_count} صنف</span>
                                                    )}
                                                    {invoice.payment_method && (
                                                        <span>{statusLabel(invoice.payment_method)}</span>
                                                    )}
                                                    {invoice.cashier_name && (
                                                        <span>{invoice.cashier_name}</span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between gap-4 lg:justify-end">
                                            <div className="text-left lg:min-w-[120px] lg:text-center">
                                                <p className="text-lg font-bold text-emerald-600">
                                                    {formatCurrency(invoice.total)}
                                                </p>
                                            </div>

                                            <div className="flex items-center gap-1.5">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="gap-1.5 border-slate-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                    disabled={isVoid}
                                                    asChild={!isVoid}
                                                >
                                                    {isVoid ? (
                                                        <span>
                                                            <Pencil className="size-4" />
                                                            <span className="hidden sm:inline">تعديل</span>
                                                        </span>
                                                    ) : (
                                                        <Link href={`/pos/invoices/${invoice.id}/edit`}>
                                                            <Pencil className="size-4" />
                                                            <span className="hidden sm:inline">تعديل</span>
                                                        </Link>
                                                    )}
                                                </Button>

                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="gap-1.5 border-slate-200 hover:border-slate-300 hover:bg-slate-50"
                                                    onClick={() => handlePrint(invoice.id)}
                                                >
                                                    <Printer className="size-4" />
                                                    <span className="hidden sm:inline">طباعة</span>
                                                </Button>

                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="gap-1.5 border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700"
                                                    disabled={isVoid}
                                                    onClick={() => setDeleteTarget(invoice)}
                                                >
                                                    <Trash2 className="size-4" />
                                                    <span className="hidden sm:inline">حذف</span>
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </section>
            </main>

            <Dialog open={deleteTarget !== null} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent dir="rtl">
                    <DialogHeader>
                        <DialogTitle>حذف الفاتورة</DialogTitle>
                        <DialogDescription>
                            هل أنت متأكد من حذف الفاتورة{' '}
                            <strong>#{deleteTarget?.invoice_number ?? deleteTarget?.id}</strong>؟
                            <br />
                            سيتم إلغاء الفاتورة وإرجاع المخزون والمبلغ للصندوق.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button variant="outline" onClick={() => setDeleteTarget(null)} disabled={deleting}>
                            إلغاء
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete} disabled={deleting}>
                            {deleting && <Spinner />}
                            تأكيد الحذف
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
