export type ReceiptSectionType =
    | 'brand'
    | 'header_meta'
    | 'invoice_meta'
    | 'items'
    | 'totals'
    | 'currency_conversions'
    | 'payment'
    | 'custom_text'
    | 'divider'
    | 'spacer';

export type ReceiptSectionProps = {
    text?: string;
    align?: 'right' | 'center' | 'left';
    size?: 'sm' | 'md' | 'lg';
    height?: number;

    // brand
    show_logo?: boolean;
    show_name?: boolean;
    show_tagline?: boolean;
    name_override?: string;
    tagline_override?: string;

    // header_meta
    show_phone?: boolean;
    show_address?: boolean;
    show_tax_number?: boolean;
    tax_number_label?: string;

    // invoice_meta
    show_title?: boolean;
    show_date?: boolean;
    show_invoice_number?: boolean;
    show_cashier?: boolean;
    show_status?: boolean;
    title?: string;
    invoice_number_label?: string;
    cashier_label?: string;
    status_label?: string;

    // items
    show_unit_price?: boolean;
    show_qty?: boolean;
    show_item_total?: boolean;
    item_label?: string;
    qty_label?: string;
    price_label?: string;
    amount_label?: string;

    // totals
    show_subtotal?: boolean;
    show_discount?: boolean;
    show_tax?: boolean;
    show_total?: boolean;
    subtotal_label?: string;
    discount_label?: string;
    tax_amount_label?: string;
    total_label?: string;

    // currency
    show_conversions_title?: boolean;
    conversions_title?: string;

    // payment
    show_payment_method?: boolean;
    show_paid?: boolean;
    payment_method_label?: string;
    paid_label?: string;
};

export type ReceiptSection = {
    id: string;
    type: ReceiptSectionType;
    enabled: boolean;
    props?: ReceiptSectionProps;
};

export type ReceiptTemplate = {
    /** 0 = auto (fill printer page). Common: 58, 80, 210 (A4) */
    paper_width_mm: number;
    sections: ReceiptSection[];
};

export const PAPER_SIZE_OPTIONS = [
    { value: 0, label: 'تلقائي', description: 'يتكيّف مع مقاس الطابعة' },
    { value: 58, label: '58مم', description: 'حراري ضيق' },
    { value: 80, label: '80مم', description: 'حراري قياسي' },
    { value: 210, label: 'A4', description: 'ورق عادي' },
] as const;

export type ReceiptInvoiceItem = {
    product_name: string;
    quantity: number;
    unit_price: number;
    total: number;
};

export type ReceiptInvoice = {
    invoice_number?: string | null;
    status: string;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    paid: number;
    created_at?: string | null;
    cashier_name?: string | null;
    payment_method?: string | null;
    items: ReceiptInvoiceItem[];
};

export type ReceiptConversion = {
    code: string;
    name: string;
    symbol: string;
    amount: number;
    formatted: string;
};

export type ReceiptBusiness = {
    name: string;
    logo?: string | null;
    tagline?: string | null;
    phone?: string | null;
    address?: string | null;
    tax_number?: string | null;
};

export const SECTION_LABELS: Record<ReceiptSectionType, string> = {
    brand: 'الشعار والاسم',
    header_meta: 'بيانات التواصل',
    invoice_meta: 'بيانات الفاتورة',
    items: 'بنود الفاتورة',
    totals: 'المجاميع',
    currency_conversions: 'التصريف',
    payment: 'الدفع',
    custom_text: 'نص حر',
    divider: 'فاصل',
    spacer: 'مسافة',
};

/** Default true when undefined */
export function showFlag(
    props: ReceiptSectionProps | undefined,
    key: keyof ReceiptSectionProps,
    fallback = true,
): boolean {
    const value = props?.[key];
    if (typeof value === 'boolean') {
        return value;
    }
    return fallback;
}

export function labelOf(
    props: ReceiptSectionProps | undefined,
    key: keyof ReceiptSectionProps,
    fallback: string,
): string {
    const value = props?.[key];
    if (typeof value === 'string' && value.trim() !== '') {
        return value.trim();
    }
    return fallback;
}

export type SectionFieldToggle = {
    key: keyof ReceiptSectionProps;
    label: string;
};

export type SectionTextField = {
    key: keyof ReceiptSectionProps;
    label: string;
    placeholder?: string;
    multiline?: boolean;
};

export const SECTION_FIELD_TOGGLES: Partial<Record<ReceiptSectionType, SectionFieldToggle[]>> = {
    brand: [
        { key: 'show_logo', label: 'إظهار الشعار' },
        { key: 'show_name', label: 'إظهار الاسم' },
        { key: 'show_tagline', label: 'إظهار الشعار النصي' },
    ],
    header_meta: [
        { key: 'show_phone', label: 'إظهار الهاتف' },
        { key: 'show_address', label: 'إظهار العنوان' },
        { key: 'show_tax_number', label: 'إظهار الرقم الضريبي' },
    ],
    invoice_meta: [
        { key: 'show_title', label: 'إظهار العنوان' },
        { key: 'show_date', label: 'إظهار التاريخ' },
        { key: 'show_invoice_number', label: 'إظهار رقم الفاتورة' },
        { key: 'show_cashier', label: 'إظهار الكاشير' },
        { key: 'show_status', label: 'إظهار الحالة' },
    ],
    items: [
        { key: 'show_qty', label: 'إظهار الكمية' },
        { key: 'show_unit_price', label: 'إظهار سعر الوحدة' },
        { key: 'show_item_total', label: 'إظهار المبلغ' },
    ],
    totals: [
        { key: 'show_subtotal', label: 'إظهار المجموع الفرعي' },
        { key: 'show_discount', label: 'إظهار الخصم' },
        { key: 'show_tax', label: 'إظهار الضريبة' },
        { key: 'show_total', label: 'إظهار الإجمالي' },
    ],
    currency_conversions: [{ key: 'show_conversions_title', label: 'إظهار عنوان التصريف' }],
    payment: [
        { key: 'show_payment_method', label: 'إظهار طريقة الدفع' },
        { key: 'show_paid', label: 'إظهار المدفوع' },
    ],
};

export const SECTION_TEXT_FIELDS: Partial<Record<ReceiptSectionType, SectionTextField[]>> = {
    brand: [
        { key: 'name_override', label: 'اسم مخصص (اختياري)', placeholder: 'اتركه فارغاً لاسم النشاط' },
        {
            key: 'tagline_override',
            label: 'شعار نصي مخصص (اختياري)',
            placeholder: 'اتركه فارغاً لشعار الموقع',
        },
    ],
    header_meta: [
        { key: 'tax_number_label', label: 'تسمية الرقم الضريبي', placeholder: 'الرقم الضريبي' },
    ],
    invoice_meta: [
        { key: 'title', label: 'عنوان الفاتورة', placeholder: 'فاتورة مبيعات' },
        { key: 'invoice_number_label', label: 'تسمية رقم الفاتورة', placeholder: 'رقم الفاتورة' },
        { key: 'cashier_label', label: 'تسمية الكاشير', placeholder: 'الكاشير' },
        { key: 'status_label', label: 'تسمية الحالة', placeholder: 'الحالة' },
    ],
    items: [
        { key: 'item_label', label: 'تسمية الصنف', placeholder: 'الصنف' },
        { key: 'qty_label', label: 'تسمية الكمية', placeholder: 'الكمية' },
        { key: 'price_label', label: 'تسمية السعر', placeholder: 'السعر' },
        { key: 'amount_label', label: 'تسمية المبلغ', placeholder: 'المبلغ' },
    ],
    totals: [
        { key: 'subtotal_label', label: 'تسمية المجموع الفرعي', placeholder: 'المجموع الفرعي' },
        { key: 'discount_label', label: 'تسمية الخصم', placeholder: 'الخصم' },
        { key: 'tax_amount_label', label: 'تسمية الضريبة', placeholder: 'الضريبة' },
        { key: 'total_label', label: 'تسمية الإجمالي', placeholder: 'الإجمالي' },
    ],
    currency_conversions: [
        { key: 'conversions_title', label: 'عنوان التصريف', placeholder: 'التصريف' },
    ],
    payment: [
        { key: 'payment_method_label', label: 'تسمية طريقة الدفع', placeholder: 'طريقة الدفع' },
        { key: 'paid_label', label: 'تسمية المدفوع', placeholder: 'المدفوع' },
    ],
};

export function createSectionId(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `sec-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

export function makeSection(
    type: ReceiptSectionType,
    props: ReceiptSectionProps = {},
): ReceiptSection {
    const defaults: Partial<Record<ReceiptSectionType, ReceiptSectionProps>> = {
        items: { show_unit_price: false, show_qty: true, show_item_total: true },
        custom_text: { text: 'نص جديد', align: 'center', size: 'sm' },
        spacer: { height: 12 },
        brand: { show_logo: true, show_name: true, show_tagline: true },
        header_meta: { show_phone: true, show_address: true, show_tax_number: true },
        invoice_meta: {
            show_title: true,
            show_date: true,
            show_invoice_number: true,
            show_cashier: true,
            show_status: true,
            title: 'فاتورة مبيعات',
        },
        totals: {
            show_subtotal: true,
            show_discount: true,
            show_tax: true,
            show_total: true,
        },
        payment: { show_payment_method: true, show_paid: true },
        currency_conversions: { show_conversions_title: true },
    };

    return {
        id: createSectionId(),
        type,
        enabled: true,
        props: { ...defaults[type], ...props },
    };
}
