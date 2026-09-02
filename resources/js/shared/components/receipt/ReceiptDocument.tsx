import ThermalLogo from '@/components/receipt/ThermalLogo';
import { formatCurrency, formatDate, statusLabel } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    labelOf,
    showFlag,
    type ReceiptBusiness,
    type ReceiptConversion,
    type ReceiptInvoice,
    type ReceiptSection,
    type ReceiptTemplate,
} from '@/types/receipt';

type Props = {
    template: ReceiptTemplate;
    invoice: ReceiptInvoice;
    business: ReceiptBusiness;
    currencyConversions?: ReceiptConversion[];
    className?: string;
    mode?: 'preview' | 'print';
};

function formatQty(value: number): string {
    return Number.isInteger(value) ? String(value) : value.toFixed(2);
}

function SectionView({
    section,
    invoice,
    business,
    currencyConversions,
}: {
    section: ReceiptSection;
    invoice: ReceiptInvoice;
    business: ReceiptBusiness;
    currencyConversions: ReceiptConversion[];
}) {
    const props = section.props ?? {};

    switch (section.type) {
        case 'brand': {
            const name = labelOf(props, 'name_override', business.name);
            const tagline = labelOf(props, 'tagline_override', business.tagline ?? '');
            const showLogo = showFlag(props, 'show_logo');
            const showName = showFlag(props, 'show_name');
            const showTagline = showFlag(props, 'show_tagline');

            if (!showLogo && !showName && !(showTagline && tagline)) {
                return null;
            }

            return (
                <div className="mb-3 text-center text-black">
                    {showLogo && business.logo ? (
                        <ThermalLogo src={business.logo} size={96} />
                    ) : null}
                    {showName ? <p className="text-base font-bold text-black">{name}</p> : null}
                    {showTagline && tagline ? (
                        <p className="mt-0.5 text-[11px] font-medium text-black">{tagline}</p>
                    ) : null}
                </div>
            );
        }

        case 'header_meta': {
            const showPhone = showFlag(props, 'show_phone');
            const showAddress = showFlag(props, 'show_address');
            const showTax = showFlag(props, 'show_tax_number');
            const taxLabel = labelOf(props, 'tax_number_label', 'الرقم الضريبي');

            if (
                !(showPhone && business.phone) &&
                !(showAddress && business.address) &&
                !(showTax && business.tax_number)
            ) {
                return null;
            }

            return (
                <div className="mb-3 space-y-0.5 text-center text-[11px] text-black">
                    {showPhone && business.phone ? <p>{business.phone}</p> : null}
                    {showAddress && business.address ? <p>{business.address}</p> : null}
                    {showTax && business.tax_number ? (
                        <p>
                            {taxLabel}: {business.tax_number}
                        </p>
                    ) : null}
                </div>
            );
        }

        case 'invoice_meta': {
            const showTitle = showFlag(props, 'show_title');
            const showDate = showFlag(props, 'show_date');
            const showNumber = showFlag(props, 'show_invoice_number');
            const showCashier = showFlag(props, 'show_cashier');
            const showStatus = showFlag(props, 'show_status');
            const title = labelOf(props, 'title', 'فاتورة مبيعات');
            const numberLabel = labelOf(props, 'invoice_number_label', 'رقم الفاتورة');
            const cashierLabel = labelOf(props, 'cashier_label', 'الكاشير');
            const statusFieldLabel = labelOf(props, 'status_label', 'الحالة');

            return (
                <div className="mb-3 space-y-1 text-xs text-black">
                    {(showTitle || (showDate && invoice.created_at)) && (
                        <div className="text-center">
                            {showTitle ? <p className="text-sm font-bold">{title}</p> : null}
                            {showDate && invoice.created_at ? (
                                <p className="mt-0.5 text-[11px]">{formatDate(invoice.created_at)}</p>
                            ) : null}
                        </div>
                    )}
                    {showNumber ? (
                        <div className="flex justify-between">
                            <span>{numberLabel}</span>
                            <span className="font-bold">#{invoice.invoice_number}</span>
                        </div>
                    ) : null}
                    {showCashier && invoice.cashier_name ? (
                        <div className="flex justify-between">
                            <span>{cashierLabel}</span>
                            <span>{invoice.cashier_name}</span>
                        </div>
                    ) : null}
                    {showStatus ? (
                        <div className="flex justify-between">
                            <span>{statusFieldLabel}</span>
                            <span>{statusLabel(invoice.status)}</span>
                        </div>
                    ) : null}
                </div>
            );
        }

        case 'items': {
            const showUnit = showFlag(props, 'show_unit_price', false);
            const showQty = showFlag(props, 'show_qty');
            const showAmount = showFlag(props, 'show_item_total');
            const itemLabel = labelOf(props, 'item_label', 'الصنف');
            const qtyLabel = labelOf(props, 'qty_label', 'الكمية');
            const priceLabel = labelOf(props, 'price_label', 'السعر');
            const amountLabel = labelOf(props, 'amount_label', 'المبلغ');

            return (
                <div className="mb-3 text-black">
                    <table className="w-full border-collapse text-[11px] leading-5">
                        <thead>
                            <tr className="border-b border-black">
                                <th className="pb-1 pe-1 text-right font-bold">{itemLabel}</th>
                                {showQty ? (
                                    <th className="w-[18%] pb-1 text-center font-bold">{qtyLabel}</th>
                                ) : null}
                                {showUnit ? (
                                    <th className="w-[22%] pb-1 text-left font-bold" dir="ltr">
                                        {priceLabel}
                                    </th>
                                ) : null}
                                {showAmount ? (
                                    <th
                                        className={cn(
                                            'pb-1 text-left font-bold',
                                            showUnit ? 'w-[22%]' : 'w-[28%]',
                                        )}
                                        dir="ltr"
                                    >
                                        {amountLabel}
                                    </th>
                                ) : null}
                            </tr>
                        </thead>
                        <tbody>
                            {invoice.items.map((item, index) => (
                                <tr
                                    key={`${item.product_name}-${index}`}
                                    className="border-b border-dashed border-black/40 last:border-b-0"
                                >
                                    <td className="py-1.5 pe-1 align-top text-right text-xs font-medium">
                                        {item.product_name}
                                    </td>
                                    {showQty ? (
                                        <td className="py-1.5 align-top text-center text-xs tabular-nums">
                                            {formatQty(item.quantity)}
                                        </td>
                                    ) : null}
                                    {showUnit ? (
                                        <td
                                            className="py-1.5 align-top text-left text-xs tabular-nums"
                                            dir="ltr"
                                        >
                                            {formatCurrency(item.unit_price)}
                                        </td>
                                    ) : null}
                                    {showAmount ? (
                                        <td
                                            className="py-1.5 align-top text-left text-xs font-medium tabular-nums"
                                            dir="ltr"
                                        >
                                            {formatCurrency(item.total)}
                                        </td>
                                    ) : null}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            );
        }

        case 'totals': {
            const showSubtotal = showFlag(props, 'show_subtotal');
            const showDiscount = showFlag(props, 'show_discount');
            const showTax = showFlag(props, 'show_tax');
            const showTotal = showFlag(props, 'show_total');

            return (
                <div className="mb-3 space-y-1 text-xs text-black">
                    {showSubtotal ? (
                        <div className="flex justify-between">
                            <span>{labelOf(props, 'subtotal_label', 'المجموع الفرعي')}</span>
                            <span>{formatCurrency(invoice.subtotal)}</span>
                        </div>
                    ) : null}
                    {showDiscount && invoice.discount > 0 ? (
                        <div className="flex justify-between">
                            <span>{labelOf(props, 'discount_label', 'الخصم')}</span>
                            <span>-{formatCurrency(invoice.discount)}</span>
                        </div>
                    ) : null}
                    {showTax && invoice.tax > 0 ? (
                        <div className="flex justify-between">
                            <span>{labelOf(props, 'tax_amount_label', 'الضريبة')}</span>
                            <span>{formatCurrency(invoice.tax)}</span>
                        </div>
                    ) : null}
                    {showTotal ? (
                        <div className="flex justify-between border-t border-dashed border-black pt-2 text-base font-bold">
                            <span>{labelOf(props, 'total_label', 'الإجمالي')}</span>
                            <span>{formatCurrency(invoice.total)}</span>
                        </div>
                    ) : null}
                </div>
            );
        }

        case 'currency_conversions':
            if (currencyConversions.length === 0) {
                return null;
            }
            return (
                <div className="mb-3 space-y-1 border-t border-dashed border-black pt-2 text-[11px] text-black">
                    {showFlag(props, 'show_conversions_title') ? (
                        <p className="font-bold">
                            {labelOf(props, 'conversions_title', 'التصريف')}
                        </p>
                    ) : null}
                    {currencyConversions.map((row) => (
                        <div key={row.code} className="flex justify-between" dir="ltr">
                            <span>{row.code}</span>
                            <span>{row.formatted}</span>
                        </div>
                    ))}
                </div>
            );

        case 'payment': {
            const showMethod = showFlag(props, 'show_payment_method');
            const showPaid = showFlag(props, 'show_paid');
            if (!showMethod && !showPaid) {
                return null;
            }
            return (
                <div className="mb-3 space-y-1 text-xs text-black">
                    {showMethod ? (
                        <div className="flex justify-between">
                            <span>{labelOf(props, 'payment_method_label', 'طريقة الدفع')}</span>
                            <span>{statusLabel(invoice.payment_method ?? 'cash')}</span>
                        </div>
                    ) : null}
                    {showPaid ? (
                        <div className="flex justify-between font-bold">
                            <span>{labelOf(props, 'paid_label', 'المدفوع')}</span>
                            <span>{formatCurrency(invoice.paid)}</span>
                        </div>
                    ) : null}
                </div>
            );
        }

        case 'custom_text': {
            const align =
                props.align === 'left'
                    ? 'text-left'
                    : props.align === 'right'
                      ? 'text-right'
                      : 'text-center';
            const size =
                props.size === 'lg' ? 'text-sm font-bold' : props.size === 'md' ? 'text-xs' : 'text-[11px]';
            return (
                <p className={cn('mb-2 whitespace-pre-wrap text-black', align, size)}>
                    {props.text || ''}
                </p>
            );
        }

        case 'divider':
            return <div className="my-2 border-t border-dashed border-black" />;

        case 'spacer':
            return <div style={{ height: props.height ?? 12 }} />;

        default:
            return null;
    }
}

export default function ReceiptDocument({
    template,
    invoice,
    business,
    currencyConversions = [],
    className,
    mode = 'preview',
}: Props) {
    const sections = template.sections.filter((section) => section.enabled);
    const configuredWidth = template.paper_width_mm ?? 80;
    const isPrint = mode === 'print';
    const isAuto = configuredWidth <= 0;
    const previewWidthMm = isAuto ? 80 : configuredWidth;

    return (
        <article
            className={cn(
                'receipt-document mx-auto bg-white px-3 py-4 text-[13px] leading-7 text-black',
                isPrint && 'receipt-document--print w-full max-w-none',
                className,
            )}
            style={
                isPrint
                    ? {
                          width: '100%',
                          maxWidth: '100%',
                          fontFamily: "'Cairo', 'Tajawal', ui-sans-serif, system-ui, sans-serif",
                      }
                    : {
                          width: `${previewWidthMm}mm`,
                          maxWidth: '100%',
                          fontFamily: "'Cairo', 'Tajawal', ui-sans-serif, system-ui, sans-serif",
                      }
            }
            dir="rtl"
            lang="ar"
            data-paper-mm={isAuto ? 'auto' : String(configuredWidth)}
        >
            {sections.map((section) => (
                <SectionView
                    key={section.id}
                    section={section}
                    invoice={invoice}
                    business={business}
                    currencyConversions={currencyConversions}
                />
            ))}
        </article>
    );
}
