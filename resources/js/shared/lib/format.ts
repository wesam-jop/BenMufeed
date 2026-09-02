export type MoneyConfig = {
    code: string;
    name?: string;
    symbol: string;
    symbol_position: 'before' | 'after';
    decimal_places: number;
};

let moneyConfig: MoneyConfig = {
    code: 'SAR',
    name: 'ريال سعودي',
    symbol: 'ر.س',
    symbol_position: 'after',
    decimal_places: 2,
};

export function setMoneyConfig(config: Partial<MoneyConfig> | null | undefined): void {
    if (!config) {
        return;
    }

    moneyConfig = {
        ...moneyConfig,
        ...config,
        symbol_position: config.symbol_position === 'before' ? 'before' : 'after',
        decimal_places:
            typeof config.decimal_places === 'number' ? config.decimal_places : moneyConfig.decimal_places,
    };
}

export function getMoneyConfig(): MoneyConfig {
    return moneyConfig;
}

export function formatCurrency(amount: number, currency?: string): string {
    const decimals = moneyConfig.decimal_places;
    const value = Number.isFinite(amount) ? amount : 0;
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(value);

    // Prefer configured primary display; ignore ISO currency glyphs like ر.س.‏
    if (!currency || currency === moneyConfig.code) {
        return moneyConfig.symbol_position === 'before'
            ? `${moneyConfig.symbol} ${formatted}`
            : `${formatted} ${moneyConfig.symbol}`;
    }

    try {
        return new Intl.NumberFormat('ar-SA', {
            style: 'currency',
            currency,
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(value);
    } catch {
        return `${formatted} ${currency}`;
    }
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat('ar-SA').format(value);
}

export function formatDate(value: string | Date): string {
    const date = typeof value === 'string' ? new Date(value) : value;

    return new Intl.DateTimeFormat('ar-SA', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export function statusLabel(status: string): string {
    const labels: Record<string, string> = {
        active: 'نشط',
        inactive: 'غير نشط',
        pending: 'قيد الانتظار',
        confirmed: 'مؤكد',
        completed: 'مكتمل',
        cancelled: 'ملغي',
        rejected: 'مرفوض',
        preparing: 'قيد التحضير',
        ready: 'جاهز',
        delivered: 'تم التسليم',
        draft: 'مسودة',
        paid: 'مدفوع',
        issued: 'صادرة',
        partial: 'مدفوعة جزئياً',
        void: 'ملغاة',
        overdue: 'متأخرة',
        open: 'مفتوح',
        closed: 'مغلق',
        free: 'فارغة',
        occupied: 'مشغولة',
        reserved: 'محجوزة',
        cleaning: 'تنظيف',
        cash: 'نقدي',
        card: 'بطاقة',
    };

    return labels[status] ?? status;
}

export function invoiceStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'paid':
            return 'default';
        case 'issued':
        case 'partial':
            return 'secondary';
        case 'void':
            return 'destructive';
        default:
            return 'outline';
    }
}

export function orderStatusLabel(status: string): string {
    return statusLabel(status);
}

export function orderTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        dine_in: 'طاولة',
        takeaway: 'سفري',
        delivery: 'توصيل',
        online: 'أونلاين',
        drive_through: 'سيارات',
    };

    return labels[type] ?? type;
}

export function orderTypeKitchenStyle(type: string): {
    badge: string;
    card: string;
    accent: string;
} {
    switch (type) {
        case 'delivery':
        case 'online':
            return {
                badge: 'bg-amber-500 text-white hover:bg-amber-500',
                card: 'border-amber-300 bg-amber-50/80',
                accent: 'text-amber-800',
            };
        case 'takeaway':
            return {
                badge: 'bg-emerald-600 text-white hover:bg-emerald-600',
                card: 'border-emerald-300 bg-emerald-50/80',
                accent: 'text-emerald-800',
            };
        case 'dine_in':
            return {
                badge: 'bg-blue-600 text-white hover:bg-blue-600',
                card: 'border-blue-300 bg-blue-50/80',
                accent: 'text-blue-800',
            };
        default:
            return {
                badge: 'bg-slate-600 text-white hover:bg-slate-600',
                card: 'border-slate-200 bg-white',
                accent: 'text-slate-800',
            };
    }
}

export function formatQuantity(value: number): string {
    return Number.isInteger(value) ? String(value) : value.toFixed(2).replace(/\.?0+$/, '');
}

export function stockMovementTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        in: 'إدخال',
        out: 'إخراج',
        adjustment: 'تعديل',
        transfer_in: 'تحويل وارد',
        transfer_out: 'تحويل صادر',
        production_in: 'إنتاج وارد',
        production_out: 'إنتاج صادر',
        wastage: 'هدر',
        return_in: 'مرتجع وارد',
        return_out: 'مرتجع صادر',
        opening_balance: 'رصيد افتتاحي',
    };

    return labels[type] ?? type;
}

export function stockMovementDirection(
    type: string,
): 'in' | 'out' | 'neutral' {
    switch (type) {
        case 'in':
        case 'transfer_in':
        case 'production_in':
        case 'return_in':
        case 'opening_balance':
            return 'in';
        case 'out':
        case 'transfer_out':
        case 'production_out':
        case 'wastage':
        case 'return_out':
            return 'out';
        default:
            return 'neutral';
    }
}

export function stockMovementTypeVariant(
    type: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    const direction = stockMovementDirection(type);

    if (direction === 'in') {
        return 'default';
    }

    if (direction === 'out') {
        return 'destructive';
    }

    return 'secondary';
}

export function stockCountStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        draft: 'مسودة',
        approved: 'معتمد',
        completed: 'مكتمل',
    };

    return labels[status] ?? status;
}

export function stockCountStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'approved':
        case 'completed':
            return 'default';
        case 'draft':
            return 'secondary';
        default:
            return 'outline';
    }
}

export function stockTransferStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        draft: 'مسودة',
        completed: 'منفّذ',
    };

    return labels[status] ?? status;
}

export function wastageReasonLabel(reason: string): string {
    const labels: Record<string, string> = {
        expired: 'منتهي الصلاحية',
        damaged: 'تالف',
        spillage: 'انسكاب',
        overproduction: 'إنتاج زائد',
        theft: 'سرقة',
        other: 'أخرى',
    };

    return labels[reason] ?? reason;
}

export function roleLabel(role: string): string {
    const labels: Record<string, string> = {
        admin: 'مدير النظام',
        manager: 'مدير فرع',
        cashier: 'كاشير',
        kitchen: 'مطبخ',
    };

    return labels[role] ?? role;
}

export function warehouseTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        main: 'رئيسي',
        kitchen: 'مطبخ',
        cold_storage: 'تبريد',
        retail: 'بيع',
        production: 'إنتاج',
    };

    return labels[type] ?? type;
}

export function stockStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        ok: 'متوفر',
        low: 'منخفض',
        out: 'نفد',
        negative: 'سالب',
    };

    return labels[status] ?? status;
}

export function auditEventVariant(
    event: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (event.includes('void') || event.includes('refund')) {
        return 'destructive';
    }

    if (event.includes('completed') || event.includes('approved') || event.includes('received')) {
        return 'default';
    }

    return 'secondary';
}

export function stockStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'ok':
            return 'default';
        case 'low':
            return 'secondary';
        case 'out':
            return 'outline';
        case 'negative':
            return 'destructive';
        default:
            return 'outline';
    }
}

export function paymentMethodLabel(method: string): string {
    const labels: Record<string, string> = {
        cash: 'نقدي',
        card: 'بطاقة',
        bank_transfer: 'تحويل بنكي',
        wallet: 'محفظة',
        loyalty: 'ولاء',
        other: 'أخرى',
    };

    return labels[method] ?? method;
}

export function invoiceStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        draft: 'مسودة',
        issued: 'صادرة',
        paid: 'مدفوعة',
        partial: 'مدفوعة جزئياً',
        void: 'ملغاة',
        overdue: 'متأخرة',
    };

    return labels[status] ?? statusLabel(status);
}

export function accountTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        asset: 'أصول',
        liability: 'خصوم',
        equity: 'حقوق ملكية',
        revenue: 'إيرادات',
        expense: 'مصروفات',
    };

    return labels[type] ?? type;
}

export function journalEntryStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        draft: 'مسودة',
        posted: 'مرحّل',
        reversed: 'معكوس',
    };

    return labels[status] ?? status;
}

export function journalEntryStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'posted':
            return 'default';
        case 'draft':
            return 'secondary';
        case 'reversed':
            return 'destructive';
        default:
            return 'outline';
    }
}

export function purchaseInvoiceStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        draft: 'مسودة',
        posted: 'مُستلَم',
        paid: 'مدفوعة',
        partial: 'مدفوعة جزئياً',
        cancelled: 'ملغاة',
    };

    return labels[status] ?? status;
}

export function purchaseInvoiceStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'posted':
        case 'paid':
            return 'default';
        case 'draft':
            return 'secondary';
        case 'partial':
            return 'outline';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
}

export function stockTransferStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'completed':
            return 'default';
        case 'draft':
            return 'secondary';
        default:
            return 'outline';
    }
}

export function orderSourceLabel(source: string): string {
    const labels: Record<string, string> = {
        pos: 'كاشير',
        online: 'موقع',
        qr: 'QR',
        admin: 'إدارة',
    };

    return labels[source] ?? source;
}

export function orderStatusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'pending':
            return 'secondary';
        case 'confirmed':
        case 'preparing':
            return 'default';
        case 'ready':
        case 'completed':
            return 'outline';
        case 'cancelled':
        case 'rejected':
            return 'destructive';
        default:
            return 'secondary';
    }
}

export type CustomerAddressLike = {
    label?: string | null;
    city?: string | null;
    district?: string | null;
    street?: string | null;
    building?: string | null;
    floor?: string | null;
    apartment?: string | null;
    notes?: string | null;
};

export function formatCustomerAddress(address: CustomerAddressLike): string {
    const parts = [
        address.label,
        address.city && address.city !== '—' ? address.city : null,
        address.district,
        address.street,
        address.building ? `مبنى ${address.building}` : null,
        address.floor ? `طابق ${address.floor}` : null,
        address.apartment ? `شقة ${address.apartment}` : null,
        address.notes,
    ].filter(Boolean);

    return parts.join(' — ') || '—';
}
