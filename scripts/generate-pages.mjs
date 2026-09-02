import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const pagesDir = path.join(__dirname, '..', 'resources', 'js', 'pages');

function ensureDir(dir) {
    fs.mkdirSync(dir, { recursive: true });
}

function writePage(relativePath, content) {
    const fullPath = path.join(pagesDir, relativePath);
    ensureDir(path.dirname(fullPath));
    if (!fs.existsSync(fullPath)) {
        fs.writeFileSync(fullPath, content, 'utf8');
        return true;
    }
    return false;
}

const indexTemplate = ({
    component,
    title,
    resource,
    resourceAr,
    propName,
    columns,
    basePath,
    createPath,
    permissionPrefix,
}) => `import { Head, Link } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type Item = Record<string, unknown> & { id: number };

type Props = {
    ${propName}: Paginated<Item>;
};

export default function ${component}({ ${propName} }: Props) {
    const items = ${propName}.data ?? [];

    return (
        <>
            <Head title="${title}" />
            <PageHeader
                title="${title}"
                description="إدارة ${resourceAr}"
                createHref="${createPath}"
                createLabel="إضافة ${resourceAr}"
            />
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    ${columns.map((c) => `<th className="px-4 py-3 font-medium">${c.label}</th>`).join('\n                                    ')}
                                    <th className="px-4 py-3 font-medium">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td colSpan={${columns.length + 1}} className="px-4 py-8 text-center text-muted-foreground">
                                            لا توجد ${resourceAr}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            ${columns.map((c) => `<td className="px-4 py-3">${c.render}</td>`).join('\n                                            ')}
                                            <td className="px-4 py-3">
                                                <ResourceActions
                                                    editHref={\`${basePath}/\${item.id}/edit\`}
                                                    deleteHref={\`${basePath}/\${item.id}\`}
                                                    editPermission="${permissionPrefix}.update"
                                                    deletePermission="${permissionPrefix}.delete"
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={${propName}} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
`;

const createTemplate = ({
    component,
    title,
    resourceAr,
    storePath,
    indexPath,
    fields,
}) => `import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function ${component}() {
    const { data, setData, post, processing, errors } = useForm({
        ${fields.map((f) => `${f.name}: ${f.default},`).join('\n        ')}
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('${storePath}');
    };

    return (
        <>
            <Head title="${title}" />
            <PageHeader title="${title}" description="إضافة ${resourceAr} جديد" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-xl gap-4">
                        ${fields.map((f) => `
                        <div className="grid gap-2">
                            <Label htmlFor="${f.name}">${f.label}</Label>
                            <Input
                                id="${f.name}"
                                type="${f.type ?? 'text'}"
                                value={String(data.${f.name} ?? '')}
                                onChange={(e) => setData('${f.name}', ${f.type === 'number' ? 'Number(e.target.value)' : 'e.target.value'})}
                                ${f.required ? 'required' : ''}
                            />
                            <InputError message={errors.${f.name}} />
                        </div>`).join('')}
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="${indexPath}">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
`;

const editTemplate = ({
    component,
    title,
    resourceAr,
    updatePath,
    indexPath,
    propName,
    fields,
}) => `import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Item = Record<string, unknown> & { id: number };

type Props = {
    ${propName}: Item;
};

export default function ${component}({ ${propName} }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        ${fields.map((f) => `${f.name}: (${propName}.${f.name} as ${f.tsType ?? 'string'}) ?? ${f.default},`).join('\n        ')}
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('${updatePath}'.replace(':id', String(${propName}.id)));
    };

    return (
        <>
            <Head title="${title}" />
            <PageHeader title="${title}" description="تعديل ${resourceAr}" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-xl gap-4">
                        ${fields.map((f) => `
                        <div className="grid gap-2">
                            <Label htmlFor="${f.name}">${f.label}</Label>
                            <Input
                                id="${f.name}"
                                type="${f.type ?? 'text'}"
                                value={String(data.${f.name} ?? '')}
                                onChange={(e) => setData('${f.name}', ${f.type === 'number' ? 'Number(e.target.value)' : 'e.target.value'})}
                                ${f.required ? 'required' : ''}
                            />
                            <InputError message={errors.${f.name}} />
                        </div>`).join('')}
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="${indexPath}">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
`;

const resources = [
    {
        path: 'admin/products',
        componentPrefix: 'Products',
        title: 'المنتجات',
        resourceAr: 'منتجات',
        propName: 'product',
        permissionPrefix: 'products',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'sku', label: 'رمز SKU', default: "''" },
            { name: 'type', label: 'النوع', default: "'simple'" },
            { name: 'sale_price', label: 'سعر البيع', default: '0', type: 'number', tsType: 'number' },
            { name: 'cost_price', label: 'سعر التكلفة', default: '0', type: 'number', tsType: 'number' },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'SKU', render: '{String(item.sku ?? "-")}' },
            { label: 'السعر', render: '{formatCurrency(Number(item.sale_price ?? 0))}' },
            { label: 'الحالة', render: '{item.is_active ? <Badge>نشط</Badge> : <Badge variant="secondary">غير نشط</Badge>}' },
        ],
    },
    {
        path: 'admin/product-categories',
        componentPrefix: 'ProductCategories',
        title: 'التصنيفات',
        resourceAr: 'تصنيفات',
        propName: 'productCategory',
        permissionPrefix: 'product-categories',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'sort_order', label: 'الترتيب', default: '0', type: 'number', tsType: 'number' },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'الترتيب', render: '{String(item.sort_order ?? "-")}' },
        ],
    },
    {
        path: 'admin/modifier-groups',
        componentPrefix: 'ModifierGroups',
        title: 'مجموعات الإضافات',
        resourceAr: 'إضافات',
        propName: 'modifierGroup',
        permissionPrefix: 'modifier-groups',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'min_selections', label: 'الحد الأدنى', default: '0', type: 'number', tsType: 'number' },
            { name: 'max_selections', label: 'الحد الأقصى', default: '1', type: 'number', tsType: 'number' },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'الحد الأدنى', render: '{String(item.min_selections ?? "-")}' },
            { label: 'الحد الأقصى', render: '{String(item.max_selections ?? "-")}' },
        ],
    },
    {
        path: 'admin/recipes',
        componentPrefix: 'Recipes',
        title: 'الوصفات',
        resourceAr: 'وصفات',
        propName: 'recipe',
        permissionPrefix: 'recipes',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'yield_quantity', label: 'الكمية الناتجة', default: '1', type: 'number', tsType: 'number' },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'الكمية', render: '{String(item.yield_quantity ?? "-")}' },
        ],
    },
    {
        path: 'admin/suppliers',
        componentPrefix: 'Suppliers',
        title: 'الموردون',
        resourceAr: 'موردين',
        propName: 'supplier',
        permissionPrefix: 'suppliers',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'phone', label: 'الهاتف', default: "''" },
            { name: 'email', label: 'البريد', default: "''", type: 'email' },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'الهاتف', render: '{String(item.phone ?? "-")}' },
            { label: 'البريد', render: '{String(item.email ?? "-")}' },
        ],
    },
    {
        path: 'admin/purchase-orders',
        componentPrefix: 'PurchaseOrders',
        title: 'أوامر الشراء',
        resourceAr: 'أوامر شراء',
        propName: 'purchaseOrder',
        permissionPrefix: 'purchase-orders',
        fields: [
            { name: 'reference', label: 'المرجع', default: "''" },
            { name: 'notes', label: 'ملاحظات', default: "''" },
        ],
        columns: [
            { label: 'المرجع', render: '{String(item.reference ?? item.id ?? "-")}' },
            { label: 'الحالة', render: '{String(item.status ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/purchase-invoices',
        componentPrefix: 'PurchaseInvoices',
        title: 'فواتير الشراء',
        resourceAr: 'فواتير شراء',
        propName: 'purchaseInvoice',
        permissionPrefix: 'purchase-invoices',
        fields: [
            { name: 'reference', label: 'المرجع', default: "''" },
            { name: 'total', label: 'الإجمالي', default: '0', type: 'number', tsType: 'number' },
        ],
        columns: [
            { label: 'المرجع', render: '{String(item.reference ?? item.id ?? "-")}' },
            { label: 'الإجمالي', render: '{formatCurrency(Number(item.total ?? 0))}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/orders',
        componentPrefix: 'Orders',
        title: 'الطلبات',
        resourceAr: 'طلبات',
        propName: 'order',
        permissionPrefix: 'orders',
        fields: [
            { name: 'notes', label: 'ملاحظات', default: "''" },
        ],
        columns: [
            { label: 'رقم الطلب', render: '{String(item.order_number ?? item.id ?? "-")}' },
            { label: 'الحالة', render: '{String(item.status ?? "-")}' },
            { label: 'الإجمالي', render: '{formatCurrency(Number(item.total ?? 0))}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/invoices',
        componentPrefix: 'Invoices',
        title: 'الفواتير',
        resourceAr: 'فواتires',
        propName: 'invoice',
        permissionPrefix: 'invoices',
        fields: [
            { name: 'notes', label: 'ملاحظات', default: "''" },
        ],
        columns: [
            { label: 'رقم الفاتورة', render: '{String(item.invoice_number ?? item.id ?? "-")}' },
            { label: 'الإجمالي', render: '{formatCurrency(Number(item.total ?? 0))}' },
            { label: 'الحالة', render: '{String(item.status ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/refunds',
        componentPrefix: 'Refunds',
        title: 'المرتجعات',
        resourceAr: 'مرتجعات',
        propName: 'refund',
        permissionPrefix: 'refunds',
        fields: [
            { name: 'reason', label: 'السبب', default: "''" },
            { name: 'amount', label: 'المبلغ', default: '0', type: 'number', tsType: 'number' },
        ],
        columns: [
            { label: 'المبلغ', render: '{formatCurrency(Number(item.amount ?? 0))}' },
            { label: 'السبب', render: '{String(item.reason ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/restaurant-tables',
        componentPrefix: 'RestaurantTables',
        title: 'الطاولات',
        resourceAr: 'طاولات',
        propName: 'restaurantTable',
        permissionPrefix: 'restaurant-tables',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'capacity', label: 'السعة', default: '4', type: 'number', tsType: 'number' },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'السعة', render: '{String(item.capacity ?? "-")}' },
            { label: 'الحالة', render: '{String(item.status ?? "-")}' },
        ],
    },
    {
        path: 'admin/production-orders',
        componentPrefix: 'ProductionOrders',
        title: 'أوامر الإنتاج',
        resourceAr: 'أوامر إنتاج',
        propName: 'productionOrder',
        permissionPrefix: 'production-orders',
        fields: [
            { name: 'quantity', label: 'الكمية', default: '1', type: 'number', tsType: 'number' },
            { name: 'notes', label: 'ملاحظات', default: "''" },
        ],
        columns: [
            { label: 'الكمية', render: '{String(item.quantity ?? "-")}' },
            { label: 'الحالة', render: '{String(item.status ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/cash-registers',
        componentPrefix: 'CashRegisters',
        title: 'الصناديق',
        resourceAr: 'صناديق',
        propName: 'cashRegister',
        permissionPrefix: 'cash-registers',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'الحالة', render: '{item.is_active ? <Badge>نشط</Badge> : <Badge variant="secondary">غير نشط</Badge>}' },
        ],
    },
    {
        path: 'admin/expenses',
        componentPrefix: 'Expenses',
        title: 'المصروفات',
        resourceAr: 'مصروفات',
        propName: 'expense',
        permissionPrefix: 'expenses',
        fields: [
            { name: 'description', label: 'الوصف', default: "''", required: true },
            { name: 'amount', label: 'المبلغ', default: '0', type: 'number', tsType: 'number' },
            { name: 'expense_date', label: 'التاريخ', default: "''", type: 'date' },
        ],
        columns: [
            { label: 'الوصف', render: '{String(item.description ?? "-")}' },
            { label: 'المبلغ', render: '{formatCurrency(Number(item.amount ?? 0))}' },
            { label: 'التاريخ', render: '{item.expense_date ? formatDate(String(item.expense_date)) : "-"}' },
        ],
    },
    {
        path: 'admin/chart-of-accounts',
        componentPrefix: 'ChartOfAccounts',
        title: 'دليل الحسابات',
        resourceAr: 'حسابات',
        propName: 'chartOfAccount',
        permissionPrefix: 'chart-of-accounts',
        fields: [
            { name: 'code', label: 'الرمز', default: "''", required: true },
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'type', label: 'النوع', default: "'asset'" },
        ],
        columns: [
            { label: 'الرمز', render: '{String(item.code ?? "-")}' },
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'النوع', render: '{String(item.type ?? "-")}' },
        ],
    },
    {
        path: 'admin/journal-entries',
        componentPrefix: 'JournalEntries',
        title: 'قيود اليومية',
        resourceAr: 'قيود',
        propName: 'journalEntry',
        permissionPrefix: 'journal-entries',
        fields: [
            { name: 'reference', label: 'المرجع', default: "''" },
            { name: 'description', label: 'الوصف', default: "''" },
        ],
        columns: [
            { label: 'المرجع', render: '{String(item.reference ?? item.id ?? "-")}' },
            { label: 'الوصف', render: '{String(item.description ?? "-")}' },
            { label: 'التاريخ', render: '{item.entry_date ? formatDate(String(item.entry_date)) : "-"}' },
        ],
    },
    {
        path: 'admin/users',
        componentPrefix: 'Users',
        title: 'المستخدمون',
        resourceAr: 'مستخدمين',
        propName: 'users',
        singularProp: 'user',
        permissionPrefix: 'users',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'username', label: 'اسم المستخدم', default: "''", required: true },
            { name: 'email', label: 'البريد', default: "''", type: 'email' },
            { name: 'password', label: 'كلمة المرور', default: "''", type: 'password' },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'اسم المستخدم', render: '{String(item.username ?? "-")}' },
            { label: 'البريد', render: '{String(item.email ?? "-")}' },
        ],
    },
    {
        path: 'admin/roles',
        componentPrefix: 'Roles',
        title: 'الأدوار',
        resourceAr: 'أدوار',
        propName: 'roles',
        singularProp: 'role',
        permissionPrefix: 'roles',
        fields: [
            { name: 'name', label: 'اسم الدور', default: "''", required: true },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'الصلاحيات', render: '{Array.isArray(item.permissions) ? item.permissions.length : 0}' },
        ],
    },
    {
        path: 'admin/stock-counts',
        componentPrefix: 'StockCounts',
        title: 'الجرد',
        resourceAr: 'جرد',
        propName: 'stockCount',
        permissionPrefix: 'stock-counts',
        fields: [
            { name: 'notes', label: 'ملاحظات', default: "''" },
        ],
        columns: [
            { label: 'المرجع', render: '{String(item.reference ?? item.id ?? "-")}' },
            { label: 'الحالة', render: '{String(item.status ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/stock-transfers',
        componentPrefix: 'StockTransfers',
        title: 'تحويلات المخزون',
        resourceAr: 'تحويلات',
        propName: 'stockTransfer',
        permissionPrefix: 'stock-transfers',
        fields: [
            { name: 'notes', label: 'ملاحظات', default: "''" },
        ],
        columns: [
            { label: 'المرجع', render: '{String(item.reference ?? item.id ?? "-")}' },
            { label: 'الحالة', render: '{String(item.status ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/wastage-records',
        componentPrefix: 'WastageRecords',
        title: 'سجلات الهدر',
        resourceAr: 'هدر',
        propName: 'wastageRecord',
        permissionPrefix: 'wastage',
        fields: [
            { name: 'quantity', label: 'الكمية', default: '0', type: 'number', tsType: 'number' },
            { name: 'reason', label: 'السبب', default: "''" },
        ],
        columns: [
            { label: 'الكمية', render: '{String(item.quantity ?? "-")}' },
            { label: 'السبب', render: '{String(item.reason ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
    },
    {
        path: 'admin/warehouses',
        componentPrefix: 'Warehouses',
        title: 'المستودعات',
        resourceAr: 'مستودعات',
        propName: 'warehouse',
        permissionPrefix: 'warehouses',
        fields: [
            { name: 'name', label: 'الاسم', default: "''", required: true },
            { name: 'code', label: 'الرمز', default: "''" },
        ],
        columns: [
            { label: 'الاسم', render: '{String(item.name ?? "-")}' },
            { label: 'الرمز', render: '{String(item.code ?? "-")}' },
        ],
    },
    {
        path: 'admin/audit-logs',
        componentPrefix: 'AuditLogs',
        title: 'سجل التدقيق',
        resourceAr: 'سجلات',
        propName: 'logs',
        permissionPrefix: 'audit-logs',
        fields: [],
        columns: [
            { label: 'الحدث', render: '{String(item.event ?? "-")}' },
            { label: 'النموذج', render: '{String(item.auditable_type ?? "-")}' },
            { label: 'المستخدم', render: '{String((item.user as Record<string, unknown>)?.name ?? "-")}' },
            { label: 'التاريخ', render: '{item.created_at ? formatDate(String(item.created_at)) : "-"}' },
        ],
        indexOnly: true,
    },
];

let created = 0;

// Dashboard
if (writePage('admin/dashboard.tsx', `import { Head } from '@inertiajs/react';
import StatCard from '@/components/domain/StatCard';
import { DollarSign, Package, Receipt, ShoppingCart } from 'lucide-react';

type Props = {
    stats: {
        orders_count: number;
        orders_total: number;
        invoices_count: number;
        invoices_total: number;
        expenses_total: number;
        stock_movements_count: number;
    };
};

export default function Dashboard({ stats }: Props) {
    return (
        <>
            <Head title="لوحة التحكم" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">لوحة التحكم</h1>
                    <p className="text-muted-foreground">نظرة عامة على أداء المقهى اليوم</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <StatCard title="طلبات اليوم" value={stats.orders_count} format="number" icon={ShoppingCart} description="إجمالي الطلبات" />
                    <StatCard title="مبيعات الطلبات" value={stats.orders_total} format="currency" icon={DollarSign} />
                    <StatCard title="الفواتير" value={stats.invoices_count} format="number" icon={Receipt} />
                    <StatCard title="إيرادات الفواتير" value={stats.invoices_total} format="currency" icon={DollarSign} />
                    <StatCard title="المصروفات" value={stats.expenses_total} format="currency" icon={Receipt} />
                    <StatCard title="حركات المخزون" value={stats.stock_movements_count} format="number" icon={Package} />
                </div>
            </div>
        </>
    );
}
`)) created++;

for (const resource of resources) {
    const basePath = `/${resource.path}`;
    const indexPath = basePath;
    const createPath = `${basePath}/create`;
    const storePath = basePath;
    const propName = resource.propName;
    const singularProp = resource.singularProp ?? propName.replace(/s$/, '');

    if (!resource.indexOnly) {
        if (writePage(`${resource.path}/create.tsx`, createTemplate({
            component: `${resource.componentPrefix}Create`,
            title: `إضافة ${resource.resourceAr}`,
            resourceAr: resource.resourceAr,
            storePath,
            indexPath,
            fields: resource.fields,
        }))) created++;

        if (writePage(`${resource.path}/edit.tsx`, editTemplate({
            component: `${resource.componentPrefix}Edit`,
            title: `تعديل ${resource.resourceAr}`,
            resourceAr: resource.resourceAr,
            updatePath: `${basePath}/:id`,
            indexPath,
            propName: singularProp,
            fields: resource.fields.filter((f) => f.name !== 'password'),
        }))) created++;
    }

    const indexProp = propName.endsWith('s') && resource.singularProp ? propName : propName;
    if (writePage(`${resource.path}/index.tsx`, indexTemplate({
        component: `${resource.componentPrefix}Index`,
        title: resource.title,
        resource: resource.path.split('/').pop(),
        resourceAr: resource.resourceAr,
        propName: indexProp,
        columns: resource.columns,
        basePath,
        createPath: resource.indexOnly ? undefined : createPath,
        permissionPrefix: resource.permissionPrefix,
    }).replace(
        resource.indexOnly
            ? `createHref="${createPath}"\n                createLabel="إضافة ${resource.resourceAr}"`
            : '',
        resource.indexOnly ? '' : `createHref="${createPath}"\n                createLabel="إضافة ${resource.resourceAr}"`,
    ))) created++;
}

// Special pages
const specialPages = {
    'admin/inventory/movements.tsx': `import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type Movement = Record<string, unknown> & { id: number };

type Props = { movements: Paginated<Movement> };

export default function InventoryMovements({ movements }: Props) {
    const items = movements.data ?? [];
    return (
        <>
            <Head title="حركات المخزون" />
            <PageHeader title="حركات المخزون" description="سجل جميع حركات المخزون" />
            <Card><CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead><tr className="border-b bg-muted/50 text-right">
                            <th className="px-4 py-3">المنتج</th>
                            <th className="px-4 py-3">النوع</th>
                            <th className="px-4 py-3">الكمية</th>
                            <th className="px-4 py-3">التاريخ</th>
                        </tr></thead>
                        <tbody>
                            {items.map((item) => (
                                <tr key={item.id} className="border-b">
                                    <td className="px-4 py-3">{String((item.product as Record<string, unknown>)?.name ?? item.product_id ?? '-')}</td>
                                    <td className="px-4 py-3">{String(item.type ?? '-')}</td>
                                    <td className="px-4 py-3">{String(item.quantity ?? '-')}</td>
                                    <td className="px-4 py-3">{item.created_at ? formatDate(String(item.created_at)) : '-'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="px-4 pb-4"><PaginationLinks paginator={movements} /></div>
            </CardContent></Card>
        </>
    );
}`,
    'admin/inventory/stock.tsx': `import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Card, CardContent } from '@/components/ui/card';
import { formatNumber } from '@/lib/format';

type StockItem = { id: number; name: string; sku?: string; quantity: number; min_stock?: number };

type Props = { stock?: StockItem[] };

export default function InventoryStock({ stock = [] }: Props) {
    return (
        <>
            <Head title="المخزون" />
            <PageHeader title="المخزون" description="مستويات المخزون الحالية" />
            <Card><CardContent className="p-0">
                <table className="w-full text-sm">
                    <thead><tr className="border-b bg-muted/50 text-right">
                        <th className="px-4 py-3">المنتج</th><th className="px-4 py-3">SKU</th>
                        <th className="px-4 py-3">الكمية</th><th className="px-4 py-3">الحد الأدنى</th>
                    </tr></thead>
                    <tbody>
                        {stock.length === 0 ? (
                            <tr><td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">لا توجد بيانات مخزون</td></tr>
                        ) : stock.map((item) => (
                            <tr key={item.id} className="border-b">
                                <td className="px-4 py-3">{item.name}</td>
                                <td className="px-4 py-3">{item.sku ?? '-'}</td>
                                <td className="px-4 py-3">{formatNumber(item.quantity)}</td>
                                <td className="px-4 py-3">{item.min_stock != null ? formatNumber(item.min_stock) : '-'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </CardContent></Card>
        </>
    );
}`,
    'admin/delivery/index.tsx': `import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type DeliveryOrder = { id: number; order_number?: string; customer_name?: string; status: string; address?: string };

type Props = { orders?: DeliveryOrder[] };

export default function DeliveryIndex({ orders = [] }: Props) {
    return (
        <>
            <Head title="التوصيل" />
            <PageHeader title="طلبات التوصيل" description="إدارة طلبات التوصيل الخارجي" />
            <Card><CardContent className="p-0">
                <table className="w-full text-sm">
                    <thead><tr className="border-b bg-muted/50 text-right">
                        <th className="px-4 py-3">الطلب</th><th className="px-4 py-3">العميل</th>
                        <th className="px-4 py-3">العنوان</th><th className="px-4 py-3">الحالة</th>
                    </tr></thead>
                    <tbody>
                        {orders.length === 0 ? (
                            <tr><td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">لا توجد طلبات توصيل</td></tr>
                        ) : orders.map((order) => (
                            <tr key={order.id} className="border-b">
                                <td className="px-4 py-3">#{order.order_number ?? order.id}</td>
                                <td className="px-4 py-3">{order.customer_name ?? '-'}</td>
                                <td className="px-4 py-3">{order.address ?? '-'}</td>
                                <td className="px-4 py-3"><Badge>{order.status}</Badge></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </CardContent></Card>
        </>
    );
}`,
    'admin/restaurant-tables/floor-plan.tsx': `import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type Table = { id: number; name: string; capacity: number; status?: string; x?: number; y?: number };

type Props = { tables?: Table[] };

export default function FloorPlan({ tables = [] }: Props) {
    return (
        <>
            <Head title="مخطط الصالة" />
            <PageHeader title="مخطط الصالة" description="عرض تخطيط الطاولات" />
            <Card><CardContent className="p-6">
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    {tables.length === 0 ? (
                        <p className="col-span-full py-8 text-center text-muted-foreground">لا توجد طاولات</p>
                    ) : tables.map((table) => (
                        <div key={table.id} className="flex aspect-square flex-col items-center justify-center rounded-lg border-2 border-dashed p-4 text-center">
                            <span className="text-lg font-bold">{table.name}</span>
                            <span className="text-sm text-muted-foreground">{table.capacity} أشخاص</span>
                            <Badge className="mt-2" variant="secondary">{table.status ?? 'متاح'}</Badge>
                        </div>
                    ))}
                </div>
            </CardContent></Card>
        </>
    );
}`,
    'admin/settings/business.tsx': `import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Settings = Record<string, string>;

type Props = { settings: Settings };

export default function BusinessSettings({ settings }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        business_name: settings.business_name ?? '',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        address: settings.address ?? '',
        tax_number: settings.tax_number ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/settings/business');
    };

    return (
        <>
            <Head title="إعدادات النشاط" />
            <PageHeader title="إعدادات النشاط" description="إعدادات المقهى والفروع" />
            <Card><CardContent className="pt-6">
                <form onSubmit={submit} className="grid max-w-xl gap-4">
                    {[
                        ['business_name', 'اسم النشاط'],
                        ['phone', 'الهاتف'],
                        ['email', 'البريد الإلكتروني'],
                        ['address', 'العنوان'],
                        ['tax_number', 'الرقم الضريبي'],
                    ].map(([key, label]) => (
                        <div key={key} className="grid gap-2">
                            <Label htmlFor={key}>{label}</Label>
                            <Input id={key} value={String(data[key as keyof typeof data] ?? '')} onChange={(e) => setData(key as keyof typeof data, e.target.value)} />
                            <InputError message={errors[key as keyof typeof errors]} />
                        </div>
                    ))}
                    <Button type="submit" disabled={processing}>{processing && <Spinner />} حفظ الإعدادات</Button>
                </form>
            </CardContent></Card>
        </>
    );
}`,
};

for (const [file, content] of Object.entries(specialPages)) {
    if (writePage(file, content)) created++;
}

// Report pages
const reports = [
    ['admin/reports/sales.tsx', 'تقرير الم_sales', 'sales'],
    ['admin/reports/products.tsx', 'تقرير المنتجات', 'products'],
    ['admin/reports/inventory.tsx', 'تقرير المخزون', 'inventory'],
    ['admin/reports/customers.tsx', 'تقرير العملاء', 'customers'],
];

for (const [file, title, key] of reports) {
    if (writePage(file, `import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import StatCard from '@/components/domain/StatCard';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatNumber } from '@/lib/format';

type ReportData = Record<string, number | string | unknown[]>;

type Props = { ${key}: ReportData };

export default function Report() {
    const data = (arguments[0] as Props).${key} ?? {};
    const rows = Array.isArray(data.items) ? data.items as Record<string, unknown>[] : [];

    return (
        <>
            <Head title="${title}" />
            <PageHeader title="${title}" description="تحليل البيانات والإحصائيات" />
            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard title="الإجمالي" value={Number(data.total ?? 0)} format="currency" />
                <StatCard title="العدد" value={Number(data.count ?? rows.length)} format="number" />
            </div>
            <Card><CardContent className="p-0">
                <table className="w-full text-sm">
                    <thead><tr className="border-b bg-muted/50 text-right">
                        <th className="px-4 py-3">البند</th><th className="px-4 py-3">القيمة</th>
                    </tr></thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr><td colSpan={2} className="px-4 py-8 text-center text-muted-foreground">لا توجد بيانات</td></tr>
                        ) : rows.map((row, i) => (
                            <tr key={i} className="border-b">
                                <td className="px-4 py-3">{String(row.name ?? row.label ?? '-')}</td>
                                <td className="px-4 py-3">{typeof row.value === 'number' ? formatCurrency(row.value) : String(row.value ?? '-')}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </CardContent></Card>
        </>
    );
}
`.replace('Report()', `Report${key.charAt(0).toUpperCase() + key.slice(1)}({ ${key} }: Props)`))) created++;
}

// Accounting reports
const accountingReports = [
    ['admin/accounting/reports/profit-loss.tsx', 'قائمة الأرباح والخسائر', 'report'],
    ['admin/accounting/reports/balance-sheet.tsx', 'الميزانية العمومية', 'report'],
    ['admin/accounting/reports/trial-balance.tsx', 'ميزان المراجعة', 'report'],
];

for (const [file, title, prop] of accountingReports) {
    if (writePage(file, `import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency } from '@/lib/format';

type Line = { name: string; amount: number };
type Props = { ${prop}: { lines?: Line[]; total?: number } };

export default function AccountingReport({ ${prop} }: Props) {
    const lines = ${prop}.lines ?? [];
    return (
        <>
            <Head title="${title}" />
            <PageHeader title="${title}" />
            <Card><CardContent className="p-0">
                <table className="w-full text-sm">
                    <thead><tr className="border-b bg-muted/50 text-right">
                        <th className="px-4 py-3">البند</th><th className="px-4 py-3">المبلغ</th>
                    </tr></thead>
                    <tbody>
                        {lines.length === 0 ? (
                            <tr><td colSpan={2} className="px-4 py-8 text-center text-muted-foreground">لا توجد بيانات</td></tr>
                        ) : lines.map((line, i) => (
                            <tr key={i} className="border-b">
                                <td className="px-4 py-3">{line.name}</td>
                                <td className="px-4 py-3">{formatCurrency(line.amount)}</td>
                            </tr>
                        ))}
                        <tr className="font-bold">
                            <td className="px-4 py-3">الإجمالي</td>
                            <td className="px-4 py-3">{formatCurrency(Number(${prop}.total ?? 0))}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent></Card>
        </>
    );
}
`)) created++;
}

// POS pages
const posPages = {
    'pos/terminal.tsx': `import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CartPanel, { type CartItem } from '@/components/domain/CartPanel';
import ProductCard from '@/components/domain/ProductCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Product = { id: number; name: string; sale_price: number; category?: { name: string } };

type Props = { branchId?: number | null; products?: Product[] };

export default function PosTerminal({ products = [] }: Props) {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [search, setSearch] = useState('');

    const filtered = products.filter((p) => p.name.includes(search));
    const demoProducts = filtered.length ? filtered : [
        { id: 1, name: 'قهوة أمريكano', sale_price: 12 },
        { id: 2, name: 'كابتشino', sale_price: 15 },
        { id: 3, name: 'لاتيه', sale_price: 16 },
        { id: 4, name: 'كروissant', sale_price: 8 },
    ];

    const addToCart = (id: number) => {
        const product = demoProducts.find((p) => p.id === id);
        if (!product) return;
        setCart((prev) => {
            const existing = prev.find((i) => i.id === id);
            if (existing) return prev.map((i) => i.id === id ? { ...i, quantity: i.quantity + 1 } : i);
            return [...prev, { id, name: product.name, price: product.sale_price, quantity: 1 }];
        });
    };

    return (
        <div className="flex h-screen flex-col bg-background" dir="rtl">
            <Head title="نقطة البيع" />
            <header className="flex items-center gap-4 border-b px-4 py-3">
                <h1 className="text-xl font-bold">نقطة البيع</h1>
                <Input placeholder="بحث..." value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-xs" />
                <Button variant="outline" className="ms-auto" asChild><a href="/pos/open-session">فتح جلسة</a></Button>
            </header>
            <div className="grid flex-1 grid-cols-1 gap-4 overflow-hidden p-4 lg:grid-cols-3">
                <div className="lg:col-span-2 overflow-y-auto">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        {demoProducts.map((p) => (
                            <ProductCard key={p.id} id={p.id} name={p.name} price={p.sale_price} onAdd={addToCart} />
                        ))}
                    </div>
                </div>
                <CartPanel
                    items={cart}
                    onIncrease={(id) => setCart((prev) => prev.map((i) => i.id === id ? { ...i, quantity: i.quantity + 1 } : i))}
                    onDecrease={(id) => setCart((prev) => prev.map((i) => i.id === id && i.quantity > 1 ? { ...i, quantity: i.quantity - 1 } : i).filter((i) => !(i.id === id && i.quantity <= 1)))}
                    onRemove={(id) => setCart((prev) => prev.filter((i) => i.id !== id))}
                    onCheckout={() => alert('إتمام الدفع')}
                />
            </div>
        </div>
    );
}`,
    'pos/open-session.tsx': `import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function OpenSession() {
    const { data, setData, post, processing } = useForm({ cash_register_id: '', opening_balance: 0, notes: '' });
    const submit = (e: React.FormEvent) => { e.preventDefault(); post('/pos/sessions/open'); };
    return (
        <div className="flex min-h-screen items-center justify-center p-4" dir="rtl">
            <Head title="فتح جلسة صندوق" />
            <Card className="w-full max-w-md">
                <CardHeader><CardTitle>فتح جلسة صندوق</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2"><Label>رقم الصندوق</Label><Input value={data.cash_register_id} onChange={(e) => setData('cash_register_id', e.target.value)} required /></div>
                        <div className="grid gap-2"><Label>رصيد الافتتاح</Label><Input type="number" value={data.opening_balance} onChange={(e) => setData('opening_balance', Number(e.target.value))} required /></div>
                        <div className="grid gap-2"><Label>ملاحظات</Label><Input value={data.notes} onChange={(e) => setData('notes', e.target.value)} /></div>
                        <Button type="submit" disabled={processing}>فتح الجلسة</Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}`,
    'pos/close-session.tsx': `import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = { sessionId: number };

export default function CloseSession({ sessionId }: Props) {
    const { data, setData, post, processing } = useForm({ actual_cash: 0, notes: '' });
    const submit = (e: React.FormEvent) => { e.preventDefault(); post(\`/pos/sessions/\${sessionId}/close\`); };
    return (
        <div className="flex min-h-screen items-center justify-center p-4" dir="rtl">
            <Head title="إغلاق جلسة صندوق" />
            <Card className="w-full max-w-md">
                <CardHeader><CardTitle>إغلاق جلسة صندوق</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2"><Label>النقد الفعلي</Label><Input type="number" value={data.actual_cash} onChange={(e) => setData('actual_cash', Number(e.target.value))} required /></div>
                        <div className="grid gap-2"><Label>ملاحظات</Label><Input value={data.notes} onChange={(e) => setData('notes', e.target.value)} /></div>
                        <Button type="submit" disabled={processing}>إغلاق الجلسة</Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}`,
    'pos/tables.tsx': `import { Head, Link } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type Table = { id: number; name: string; status?: string; capacity?: number };

type Props = { tables: Table[] };

export default function PosTables({ tables = [] }: Props) {
    return (
        <div dir="rtl" className="min-h-screen p-4">
            <Head title="طاولات POS" />
            <PageHeader title="الطاولات" description="اختر طاولة لبدء الطلب" />
            <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {tables.map((table) => (
                    <Link key={table.id} href={\`/pos/tables/\${table.id}/order\`}>
                        <Card className="cursor-pointer transition-shadow hover:shadow-md">
                            <CardContent className="flex flex-col items-center p-6">
                                <span className="text-2xl font-bold">{table.name}</span>
                                <Badge className="mt-2">{table.status ?? 'متاح'}</Badge>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </div>
    );
}`,
    'pos/table-order.tsx': `import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CartPanel, { type CartItem } from '@/components/domain/CartPanel';
import ProductCard from '@/components/domain/ProductCard';

type Props = { table: { id: number; name: string }; products?: Array<{ id: number; name: string; sale_price: number }> };

export default function TableOrder({ table, products = [] }: Props) {
    const [cart, setCart] = useState<CartItem[]>([]);
    const items = products.length ? products : [
        { id: 1, name: 'قهوة', sale_price: 12 },
        { id: 2, name: 'شاي', sale_price: 8 },
    ];
    return (
        <div dir="rtl" className="grid h-screen grid-cols-1 lg:grid-cols-3 gap-4 p-4">
            <Head title={\`طاولة \${table.name}\`} />
            <div className="lg:col-span-2">
                <h1 className="mb-4 text-xl font-bold">طاولة {table.name}</h1>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {items.map((p) => (
                        <ProductCard key={p.id} id={p.id} name={p.name} price={p.sale_price} onAdd={(id) => {
                            const product = items.find((x) => x.id === id)!;
                            setCart((prev) => {
                                const ex = prev.find((i) => i.id === id);
                                if (ex) return prev.map((i) => i.id === id ? { ...i, quantity: i.quantity + 1 } : i);
                                return [...prev, { id, name: product.name, price: product.sale_price, quantity: 1 }];
                            });
                        }} />
                    ))}
                </div>
            </div>
            <CartPanel items={cart} checkoutLabel="إرسال للمطبخ" onCheckout={() => alert('تم إرسال الطلب')} />
        </div>
    );
}`,
};

for (const [file, content] of Object.entries(posPages)) {
    if (writePage(file, content)) created++;
}

// Kitchen
if (writePage('kitchen/display.tsx', `import { Head, router } from '@inertiajs/react';
import OrderTicket from '@/components/domain/OrderTicket';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

type OrderItem = { id: number; quantity: number; product?: { name: string }; status?: string };
type Order = { id: number; order_number?: string; status: string; created_at: string; items: OrderItem[]; total?: number; tableSession?: { table?: { name: string } } };

type Props = { orders: Order[]; status: string; statuses: string[] };

export default function KitchenDisplay({ orders, status, statuses }: Props) {
    return (
        <div dir="rtl" className="min-h-screen bg-background p-4">
            <Head title="شاشة المطبخ" />
            <div className="mb-6 flex flex-wrap items-center gap-2">
                <h1 className="text-2xl font-bold">شاشة المطبخ</h1>
                {statuses.map((s) => (
                    <Button key={s} variant={s === status ? 'default' : 'outline'} size="sm"
                        onClick={() => router.get('/kitchen', { status: s }, { preserveState: true })}>
                        <Badge variant="secondary">{s}</Badge>
                    </Button>
                ))}
            </div>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {orders.length === 0 ? (
                    <p className="text-muted-foreground">لا توجد طلبات</p>
                ) : orders.map((order) => (
                    <OrderTicket
                        key={order.id}
                        orderNumber={order.order_number ?? order.id}
                        status={order.status}
                        createdAt={order.created_at}
                        tableName={order.tableSession?.table?.name}
                        total={order.total}
                        items={order.items.map((item) => ({
                            id: item.id,
                            name: item.product?.name ?? 'منتج',
                            quantity: item.quantity,
                            status: item.status,
                        }))}
                        actions={<Button size="sm" onClick={() => router.patch(\`/kitchen/orders/\${order.id}/status\`, { status: 'ready' })}>جاهز</Button>}
                    />
                ))}
            </div>
        </div>
    );
}
`)) created++;

// Customer pages
const customerPages = {
    'customer/home.tsx': `import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = { customer?: { name: string }; recentOrders?: Array<{ id: number; total?: number; status?: string }> };

export default function CustomerHome({ customer, recentOrders = [] }: Props) {
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-4xl p-4">
            <Head title="الرئيسية" />
            <h1 className="mb-2 text-2xl font-bold">مرحباً {customer?.name ?? 'ضيف'}</h1>
            <p className="mb-6 text-muted-foreground">اطلب مشروباتك المفضلة</p>
            <div className="mb-8 flex gap-3">
                <Button asChild><Link href="/customer/menu">القائمة</Link></Button>
                <Button variant="outline" asChild><Link href="/customer/orders">طلباتي</Link></Button>
            </div>
            <Card><CardHeader><CardTitle>آخر الطلبات</CardTitle></CardHeader>
                <CardContent className="space-y-2">
                    {recentOrders.length === 0 ? <p className="text-muted-foreground">لا توجد طلبات</p> :
                        recentOrders.map((o) => <div key={o.id} className="flex justify-between border-b py-2"><span>طلب #{o.id}</span><span>{o.status}</span></div>)}
                </CardContent>
            </Card>
        </div>
    );
}`,
    'customer/menu.tsx': `import { Head } from '@inertiajs/react';
import ProductCard from '@/components/domain/ProductCard';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

type Product = { id: number; name: string; sale_price: number; description?: string };

type Props = { categories?: Array<{ id: number; name: string; products: Product[] }>; products?: Product[] };

export default function CustomerMenu({ categories = [], products = [] }: Props) {
    const allProducts = products.length ? products : categories.flatMap((c) => c.products);
    const [cartCount, setCartCount] = useState(0);
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-5xl p-4">
            <Head title="القائمة" />
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold">القائمة</h1>
                <Button asChild><Link href="/customer/cart">السلة ({cartCount})</Link></Button>
            </div>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                {(allProducts.length ? allProducts : [{ id: 1, name: 'لاتيه', sale_price: 16 }, { id: 2, name: 'كابتشino', sale_price: 15 }]).map((p) => (
                    <ProductCard key={p.id} id={p.id} name={p.name} price={p.sale_price} onAdd={() => setCartCount((c) => c + 1)} />
                ))}
            </div>
        </div>
    );
}`,
    'customer/cart.tsx': `import { Head, Link } from '@inertiajs/react';
import CartPanel, { type CartItem } from '@/components/domain/CartPanel';
import { useState } from 'react';

export default function CustomerCart() {
    const [items, setItems] = useState<CartItem[]>([
        { id: 1, name: 'لاتيه', price: 16, quantity: 1 },
    ]);
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-lg p-4">
            <Head title="السلة" />
            <CartPanel items={items} onCheckout={() => alert('تأكيد الطلب')} checkoutLabel="تأكيد الطلب" />
            <Link href="/customer/menu" className="mt-4 block text-center text-sm text-primary">متابعة التسوق</Link>
        </div>
    );
}`,
    'customer/orders/index.tsx': `import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';

type Order = { id: number; order_number?: string; status: string; total: number; created_at: string };

type Props = { orders: { data: Order[] } | Order[] };

export default function CustomerOrdersIndex({ orders }: Props) {
    const list = Array.isArray(orders) ? orders : orders.data ?? [];
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-3xl p-4">
            <Head title="طلباتي" />
            <h1 className="mb-6 text-2xl font-bold">طلباتي</h1>
            <div className="space-y-3">
                {list.map((order) => (
                    <Link key={order.id} href={\`/customer/orders/\${order.id}\`}>
                        <Card><CardContent className="flex items-center justify-between p-4">
                            <div><p className="font-medium">#{order.order_number ?? order.id}</p><p className="text-sm text-muted-foreground">{formatDate(order.created_at)}</p></div>
                            <div className="text-left"><Badge>{order.status}</Badge><p className="mt-1 font-bold">{formatCurrency(order.total)}</p></div>
                        </CardContent></Card>
                    </Link>
                ))}
            </div>
        </div>
    );
}`,
    'customer/orders/show.tsx': `import { Head } from '@inertiajs/react';
import OrderTicket from '@/components/domain/OrderTicket';

type Props = { order: { id: number; order_number?: string; status: string; created_at: string; total: number; items: Array<{ id: number; quantity: number; product?: { name: string }; unit_price?: number }> } };

export default function CustomerOrderShow({ order }: Props) {
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-lg p-4">
            <Head title={\`طلب #\${order.order_number ?? order.id}\`} />
            <OrderTicket
                orderNumber={order.order_number ?? order.id}
                status={order.status}
                createdAt={order.created_at}
                total={order.total}
                items={order.items.map((i) => ({ id: i.id, name: i.product?.name ?? 'منتج', quantity: i.quantity, price: i.unit_price }))}
            />
        </div>
    );
}`,
    'customer/favorites/index.tsx': `import { Head } from '@inertiajs/react';
import ProductCard from '@/components/domain/ProductCard';

type Props = { favorites?: Array<{ id: number; product: { id: number; name: string; sale_price: number } }> };

export default function CustomerFavorites({ favorites = [] }: Props) {
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-5xl p-4">
            <Head title="المفضلة" />
            <h1 className="mb-6 text-2xl font-bold">المفضلة</h1>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                {favorites.length === 0 ? <p className="text-muted-foreground">لا توجد منتجات مفضلة</p> :
                    favorites.map((f) => <ProductCard key={f.id} id={f.product.id} name={f.product.name} price={f.product.sale_price} />)}
            </div>
        </div>
    );
}`,
    'customer/addresses/index.tsx': `import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Address = { id: number; label: string; address: string; is_default?: boolean };

type Props = { addresses: Address[] };

export default function CustomerAddresses({ addresses = [] }: Props) {
    const { data, setData, post, processing } = useForm({ label: '', address: '', is_default: false });
    const submit = (e: React.FormEvent) => { e.preventDefault(); post('/customer/addresses'); };
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-lg p-4">
            <Head title="العناوين" />
            <h1 className="mb-6 text-2xl font-bold">عناوين التوصيل</h1>
            <div className="mb-6 space-y-3">
                {addresses.map((a) => (
                    <Card key={a.id}><CardContent className="p-4"><p className="font-medium">{a.label}</p><p className="text-sm text-muted-foreground">{a.address}</p></CardContent></Card>
                ))}
            </div>
            <Card><CardContent className="pt-6">
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2"><Label>التسمية</Label><Input value={data.label} onChange={(e) => setData('label', e.target.value)} required /></div>
                    <div className="grid gap-2"><Label>العنوان</Label><Input value={data.address} onChange={(e) => setData('address', e.target.value)} required /></div>
                    <Button type="submit" disabled={processing}>إضافة عنوان</Button>
                </form>
            </CardContent></Card>
        </div>
    );
}`,
    'customer/rewards.tsx': `import { Head } from '@inertiajs/react';
import StatCard from '@/components/domain/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = { points?: number; tier?: string };

export default function CustomerRewards({ points = 0, tier = 'برونزي' }: Props) {
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-3xl p-4">
            <Head title="المكافآت" />
            <h1 className="mb-6 text-2xl font-bold">برنامج المكافآت</h1>
            <div className="mb-6 grid gap-4 sm:grid-cols-2">
                <StatCard title="نقاطك" value={points} format="number" />
                <StatCard title="المستوى" value={tier} />
            </div>
            <Card><CardHeader><CardTitle>المكافآت المتاحة</CardTitle></CardHeader>
                <CardContent><p className="text-muted-foreground">استبدل نقاطك بمشروبات مجانية</p></CardContent>
            </Card>
        </div>
    );
}`,
    'customer/profile.tsx': `import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Customer = { name: string; email?: string; phone?: string };

type Props = { customer: Customer };

export default function CustomerProfile({ customer }: Props) {
    const { data, setData, patch, processing } = useForm({ name: customer.name, email: customer.email ?? '', phone: customer.phone ?? '' });
    const submit = (e: React.FormEvent) => { e.preventDefault(); patch('/customer/profile'); };
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-lg p-4">
            <Head title="الملف الشخصي" />
            <h1 className="mb-6 text-2xl font-bold">الملف الشخصي</h1>
            <Card><CardContent className="pt-6">
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2"><Label>الاسم</Label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} /></div>
                    <div className="grid gap-2"><Label>البريد</Label><Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} /></div>
                    <div className="grid gap-2"><Label>الهاتف</Label><Input value={data.phone} onChange={(e) => setData('phone', e.target.value)} /></div>
                    <Button type="submit" disabled={processing}>حفظ</Button>
                </form>
            </CardContent></Card>
        </div>
    );
}`,
    'customer/qr-order.tsx': `import { Head, useForm } from '@inertiajs/react';
import ProductCard from '@/components/domain/ProductCard';
import { Button } from '@/components/ui/button';

type Product = { id: number; name: string; sale_price: number };

type Props = { table: { id: number; name: string }; products: Product[]; qr_token: string };

export default function QROrder({ table, products, qr_token }: Props) {
    const { data, setData, post, processing } = useForm<{ items: Array<{ product_id: number; quantity: number }> }>({ items: [] });
    const addItem = (id: number) => setData('items', [...data.items.filter((i) => i.product_id !== id), { product_id: id, quantity: (data.items.find((i) => i.product_id === id)?.quantity ?? 0) + 1 }]);
    const submit = () => post(\`/order/\${qr_token}\`);
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-4xl p-4">
            <Head title={\`طاولة \${table.name}\`} />
            <h1 className="mb-2 text-2xl font-bold">طاولة {table.name}</h1>
            <p className="mb-6 text-muted-foreground">اطلب مباشرة من طاولتك</p>
            <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                {products.map((p) => <ProductCard key={p.id} id={p.id} name={p.name} price={p.sale_price} onAdd={addItem} />)}
            </div>
            <Button onClick={submit} disabled={processing || data.items.length === 0}>إرسال الطلب ({data.items.length})</Button>
        </div>
    );
}`,
    'customer/auth/login.tsx': `import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function CustomerLogin() {
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '' });
    const submit = (e: React.FormEvent) => { e.preventDefault(); post('/customer/login'); };
    return (
        <div dir="rtl" className="flex min-h-screen items-center justify-center p-4">
            <Head title="تسجيل الدخول" />
            <Card className="w-full max-w-md">
                <CardHeader><CardTitle>تسجيل الدخول</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-2"><Label>البريد</Label><Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required /><InputError message={errors.email} /></div>
                        <div className="grid gap-2"><Label>كلمة المرور</Label><Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required /><InputError message={errors.password} /></div>
                        <Button type="submit" disabled={processing}>دخول</Button>
                        <p className="text-center text-sm"><Link href="/customer/register" className="text-primary">إنشاء حساب</Link></p>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}`,
    'customer/auth/register.tsx': `import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function CustomerRegister() {
    const { data, setData, post, processing, errors } = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
    const submit = (e: React.FormEvent) => { e.preventDefault(); post('/customer/register'); };
    return (
        <div dir="rtl" className="flex min-h-screen items-center justify-center p-4">
            <Head title="إنشاء حساب" />
            <Card className="w-full max-w-md">
                <CardHeader><CardTitle>إنشاء حساب</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        {[
                            ['name', 'الاسم', 'text'],
                            ['email', 'البريد', 'email'],
                            ['phone', 'الهاتف', 'tel'],
                            ['password', 'كلمة المرور', 'password'],
                            ['password_confirmation', 'تأكيد كلمة المرور', 'password'],
                        ].map(([key, label, type]) => (
                            <div key={key} className="grid gap-2">
                                <Label>{label}</Label>
                                <Input type={type} value={String(data[key as keyof typeof data] ?? '')} onChange={(e) => setData(key as keyof typeof data, e.target.value)} required />
                                <InputError message={errors[key as keyof typeof errors]} />
                            </div>
                        ))}
                        <Button type="submit" disabled={processing}>تسجيل</Button>
                        <p className="text-center text-sm"><Link href="/customer/login" className="text-primary">لديك حساب؟ سجل دخول</Link></p>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}`,
};

for (const [file, content] of Object.entries(customerPages)) {
    if (writePage(file, content)) created++;
}

console.log(`Created ${created} pages.`);
