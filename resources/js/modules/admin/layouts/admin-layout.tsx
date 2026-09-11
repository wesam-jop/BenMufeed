import { Link } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    Calculator,
    ChefHat,
    ClipboardList,
    Coffee,
    CreditCard,
    Factory,
    FileText,
    LayoutGrid,
    Package,
    Receipt,
    Settings,
    ShoppingBag,
    ShoppingCart,
    Store,
    Table2,
    Truck,
    Users,
    Warehouse,
    ArrowLeftRight,
} from 'lucide-react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/app-logo';
import BranchSelector from '@/components/domain/BranchSelector';
import LanguageSwitcher from '@/components/language-switcher';
import { NavFooter } from '@/components/nav-footer';
import { NavUser } from '@/components/nav-user';
import { useI18n } from '@/hooks/use-i18n';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarInset,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePage } from '@inertiajs/react';
import type { NavItem } from '@/types';

type NavGroupDef = {
    labelKey: string;
    items: Array<{ titleKey: string; href: string; icon: NavItem['icon'] }>;
};

const navGroupDefs: NavGroupDef[] = [
    {
        labelKey: 'admin.group.home',
        items: [
            { titleKey: 'admin.nav.dashboard', href: '/admin', icon: LayoutGrid },
            { titleKey: 'admin.nav.pos', href: '/pos', icon: ShoppingCart },
        ],
    },
    {
        labelKey: 'admin.group.sales',
        items: [
            { titleKey: 'admin.nav.orders', href: '/admin/orders', icon: ClipboardList },
            { titleKey: 'admin.nav.invoices', href: '/admin/invoices', icon: Receipt },
            { titleKey: 'admin.nav.refunds', href: '/admin/refunds', icon: FileText },
        ],
    },
    {
        labelKey: 'admin.group.tables',
        items: [
            { titleKey: 'admin.nav.tables', href: '/admin/restaurant-tables', icon: Table2 },
            { titleKey: 'admin.nav.table_sessions', href: '/admin/table-sessions', icon: Coffee },
            { titleKey: 'admin.nav.delivery', href: '/admin/delivery', icon: Truck },
        ],
    },
    {
        labelKey: 'admin.group.kitchen',
        items: [{ titleKey: 'admin.nav.kitchen', href: '/kitchen', icon: ChefHat }],
    },
    {
        labelKey: 'admin.group.products',
        items: [
            { titleKey: 'admin.nav.products', href: '/admin/products', icon: Package },
            { titleKey: 'admin.nav.categories', href: '/admin/product-categories', icon: Store },
            { titleKey: 'admin.nav.modifiers', href: '/admin/modifier-groups', icon: ShoppingBag },
            { titleKey: 'admin.nav.recipes', href: '/admin/recipes', icon: BookOpen },
        ],
    },
    {
        labelKey: 'admin.group.production',
        items: [
            { titleKey: 'admin.nav.production_orders', href: '/admin/production-orders', icon: Factory },
        ],
    },
    {
        labelKey: 'admin.group.inventory',
        items: [
            { titleKey: 'admin.nav.stock', href: '/admin/inventory/stock', icon: Warehouse },
            { titleKey: 'admin.nav.movements', href: '/admin/inventory/movements', icon: ClipboardList },
            { titleKey: 'admin.nav.stock_counts', href: '/admin/stock-counts', icon: ClipboardList },
            { titleKey: 'admin.nav.transfers', href: '/admin/stock-transfers', icon: Truck },
            { titleKey: 'admin.nav.wastage', href: '/admin/wastage-records', icon: FileText },
            { titleKey: 'admin.nav.warehouses', href: '/admin/warehouses', icon: Warehouse },
        ],
    },
    {
        labelKey: 'admin.group.purchases',
        items: [
            { titleKey: 'admin.nav.suppliers', href: '/admin/suppliers', icon: Users },
            { titleKey: 'admin.nav.purchase_orders', href: '/admin/purchase-orders', icon: ShoppingBag },
            { titleKey: 'admin.nav.purchase_invoices', href: '/admin/purchase-invoices', icon: Receipt },
        ],
    },
    {
        labelKey: 'admin.group.finance',
        items: [
            { titleKey: 'admin.nav.cash_registers', href: '/admin/cash-registers', icon: CreditCard },
            { titleKey: 'admin.nav.cash_sessions', href: '/admin/cash-register-sessions', icon: CreditCard },
            { titleKey: 'admin.nav.expenses', href: '/admin/expenses', icon: Receipt },
            { titleKey: 'admin.nav.exchange', href: '/admin/settings/currencies', icon: ArrowLeftRight },
        ],
    },
    {
        labelKey: 'admin.group.accounting',
        items: [
            { titleKey: 'admin.nav.chart_of_accounts', href: '/admin/chart-of-accounts', icon: BookOpen },
            { titleKey: 'admin.nav.journal_entries', href: '/admin/journal-entries', icon: FileText },
            { titleKey: 'admin.nav.profit_loss', href: '/admin/accounting/reports/profit-loss', icon: Calculator },
            { titleKey: 'admin.nav.balance_sheet', href: '/admin/accounting/reports/balance-sheet', icon: BarChart3 },
            { titleKey: 'admin.nav.trial_balance', href: '/admin/accounting/reports/trial-balance', icon: Calculator },
        ],
    },
    {
        labelKey: 'admin.group.reports',
        items: [
            { titleKey: 'admin.nav.report_sales', href: '/admin/reports/sales', icon: BarChart3 },
            { titleKey: 'admin.nav.report_products', href: '/admin/reports/products', icon: Package },
            { titleKey: 'admin.nav.report_inventory', href: '/admin/reports/inventory', icon: Warehouse },
            { titleKey: 'admin.nav.report_customers', href: '/admin/reports/customers', icon: Users },
        ],
    },
    {
        labelKey: 'admin.group.management',
        items: [
            { titleKey: 'admin.nav.users', href: '/admin/users', icon: Users },
            { titleKey: 'admin.nav.roles', href: '/admin/roles', icon: Users },
            { titleKey: 'admin.nav.settings', href: '/admin/settings/business', icon: Settings },
            { titleKey: 'admin.nav.audit', href: '/admin/audit-logs', icon: ClipboardList },
        ],
    },
];

function AdminSidebar() {
    const { isCurrentUrl } = useCurrentUrl();
    const { t, dir } = useI18n();
    const sidebarSide = dir === 'ltr' ? 'left' : 'right';

    return (
        <Sidebar collapsible="icon" variant="inset" side={sidebarSide}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroupDefs.map((group) => {
                    const label = t(group.labelKey);

                    return (
                        <SidebarGroup key={group.labelKey} className="px-2 py-0">
                            <SidebarGroupLabel>{label}</SidebarGroupLabel>
                            <SidebarMenu>
                                {group.items.map((item) => {
                                    const title = t(item.titleKey);

                                    return (
                                        <SidebarMenuItem key={item.titleKey}>
                                            <SidebarMenuButton
                                                asChild
                                                isActive={isCurrentUrl(item.href)}
                                                tooltip={{ children: title }}
                                            >
                                                <Link href={item.href} prefetch>
                                                    {item.icon && <item.icon />}
                                                    <span>{title}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroup>
                    );
                })}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter
                    items={[
                        {
                            title: t('admin.nav.customer_portal'),
                            href: '/customer',
                            icon: Store,
                        },
                    ]}
                    className="mt-auto"
                />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

export default function AdminLayout({
    children,
    title,
}: {
    children: ReactNode;
    title?: string;
}) {
    const { sidebarOpen } = usePage().props;
    const { dir } = useI18n();

    return (
        <div dir={dir}>
            <SidebarProvider defaultOpen={sidebarOpen}>
                <AdminSidebar />
                <SidebarInset className="min-w-0">
                    <header className="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                        <SidebarTrigger className="-me-1" />
                        <Separator orientation="vertical" className="mx-2 h-4" />
                        {title && <h1 className="text-sm font-medium">{title}</h1>}
                        <div className="ms-auto flex items-center gap-2">
                            <LanguageSwitcher variant="ghost" />
                            <BranchSelector />
                        </div>
                    </header>
                    <main className="flex-1 overflow-auto p-4 md:p-6">{children}</main>
                </SidebarInset>
            </SidebarProvider>
        </div>
    );
}
