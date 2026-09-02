import { Head } from '@inertiajs/react';
import { useEffect } from 'react';
import BusinessBrand from '@/components/domain/BusinessBrand';
import ReceiptDocument from '@/components/receipt/ReceiptDocument';
import type {
    ReceiptBusiness,
    ReceiptConversion,
    ReceiptInvoice,
    ReceiptTemplate,
} from '@/types/receipt';

type Props = {
    invoice: ReceiptInvoice;
    currencyConversions?: ReceiptConversion[];
    receiptTemplate: ReceiptTemplate;
    receiptBusiness: ReceiptBusiness;
};

export default function InvoicePrint({
    invoice,
    currencyConversions = [],
    receiptTemplate,
    receiptBusiness,
}: Props) {
    useEffect(() => {
        let cancelled = false;

        const runPrint = async () => {
            try {
                if (document.fonts?.ready) {
                    await document.fonts.ready;
                }
                await new Promise((r) => setTimeout(r, 350));
            } catch {
                // ignore
            }

            if (!cancelled) {
                window.print();
            }
        };

        const timer = setTimeout(() => {
            void runPrint();
        }, 200);

        return () => {
            cancelled = true;
            clearTimeout(timer);
        };
    }, []);

    return (
        <div
            className="min-h-screen bg-white text-slate-900 print:min-h-0"
            dir="rtl"
            lang="ar"
            style={{ fontFamily: "'Cairo', 'Tajawal', ui-sans-serif, system-ui, sans-serif" }}
        >
            <Head title={`طباعة ${invoice.invoice_number ?? 'فاتورة'}`}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=cairo:400,500,600,700"
                    rel="stylesheet"
                />
            </Head>

            <style>{`
                .receipt-document,
                .receipt-document * {
                    font-family: 'Cairo', 'Tajawal', ui-sans-serif, system-ui, sans-serif !important;
                    -webkit-font-smoothing: antialiased;
                    text-rendering: optimizeLegibility;
                    color: #000 !important;
                }
                .receipt-document {
                    background: #fff !important;
                    box-sizing: border-box;
                }
                .receipt-document table {
                    width: 100% !important;
                    table-layout: fixed;
                }
                .receipt-thermal-logo {
                    filter: none !important;
                    -webkit-filter: none !important;
                    max-width: 28% !important;
                    height: auto !important;
                }
                @media print {
                    /* Let the printer/driver paper size control the page */
                    @page {
                        margin: 3mm;
                        size: auto;
                    }
                    html, body {
                        background: white !important;
                        width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    .no-print { display: none !important; }
                    .receipt-document,
                    .receipt-document--print {
                        width: 100% !important;
                        max-width: 100% !important;
                        margin: 0 !important;
                        padding: 2mm 1.5mm !important;
                    }
                    .receipt-document,
                    .receipt-document * {
                        font-family: 'Cairo', 'Tajawal', ui-sans-serif, system-ui, sans-serif !important;
                        color: #000 !important;
                        border-color: #000 !important;
                        print-color-adjust: exact;
                        -webkit-print-color-adjust: exact;
                    }
                }
                @media screen {
                    .receipt-document--print {
                        width: min(100%, 420px);
                        margin-inline: auto;
                        border: 1px solid #e7e5e4;
                    }
                }
            `}</style>

            <div className="no-print border-b bg-slate-900 px-4 py-3 text-white">
                <div className="mx-auto flex max-w-lg flex-wrap items-center justify-between gap-3">
                    <div>
                        <BusinessBrand
                            iconWrapperClassName="bg-white/10"
                            textClassName="text-white [&_span]:text-white"
                        />
                        <p className="mt-1 text-xs text-white/70">
                            اختر مقاس الورق من نافذة الطباعة — التصميم يتكيّف تلقائياً
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => window.print()}
                        className="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium hover:bg-white/20"
                    >
                        طباعة
                    </button>
                </div>
            </div>

            <div className="print:p-0 px-3 py-6">
                <ReceiptDocument
                    mode="print"
                    template={receiptTemplate}
                    invoice={invoice}
                    business={receiptBusiness}
                    currencyConversions={currencyConversions}
                />
            </div>
        </div>
    );
}
