import { Link } from '@inertiajs/react';
import {
    ClipboardList,
    Heart,
    LayoutGrid,
    MapPin,
    ShoppingBag,
    User,
} from 'lucide-react';
import type { ReactNode } from 'react';
import BusinessBrand from '@/components/domain/BusinessBrand';
import { NavCustomer } from '@/components/nav-customer';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePage } from '@inertiajs/react';
import type { NavItem } from '@/types';

type NavGroup = {
    label: string;
    items: NavItem[];
};

const navGroups: NavGroup[] = [
    {
        label: 'الرئيسية',
        items: [
            { title: 'لوحة التحكم', href: '/customer', icon: LayoutGrid },
            { title: 'اطلب الآن', href: '/products', icon: ShoppingBag },
        ],
    },
    {
        label: 'طلباتي',
        items: [{ title: 'الطلبات', href: '/customer/orders', icon: ClipboardList }],
    },
    {
        label: 'حسابي',
        items: [
            { title: 'مواقع التوصيل', href: '/customer/addresses', icon: MapPin },
            { title: 'المفضلة', href: '/customer/favorites', icon: Heart },
            { title: 'الملف الشخصي', href: '/customer/profile', icon: User },
        ],
    },
];

function CustomerSidebar() {
    const { isNavItemActive } = useCurrentUrl();

    const activeNavClassName =
        'data-[active=true]:bg-amber-800/10 data-[active=true]:text-amber-900 data-[active=true]:font-semibold data-[active=true]:shadow-[inset_3px_0_0_0_#92400e] dark:data-[active=true]:text-amber-200';

    return (
        <Sidebar collapsible="icon" variant="inset" side="right" className="border-amber-100/50">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/customer" prefetch>
                                <BusinessBrand
                                    subtitle="حسابي"
                                    iconWrapperClassName="rounded-lg bg-amber-800 text-white"
                                    textClassName="text-right"
                                />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <SidebarGroup key={group.label} className="px-2 py-0">
                        <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                        <SidebarMenu>
                            {group.items.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isNavItemActive(item.href)}
                                        className={activeNavClassName}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavCustomer />
            </SidebarFooter>
        </Sidebar>
    );
}

export default function CustomerLayout({
    children,
    title,
}: {
    children: ReactNode;
    title?: string;
}) {
    const { sidebarOpen } = usePage().props;

    return (
        <SidebarProvider defaultOpen={sidebarOpen}>
            <CustomerSidebar />
            <SidebarInset className="min-w-0 bg-[#faf8f5]">
                <header className="flex h-14 shrink-0 items-center gap-2 border-b border-amber-100/80 bg-white/90 px-4 backdrop-blur-md">
                    <SidebarTrigger className="-me-1" />
                    <Separator orientation="vertical" className="mx-2 h-4" />
                    {title && <h1 className="text-sm font-medium text-amber-950">{title}</h1>}
                </header>
                <main className="flex-1 overflow-auto p-4 md:p-6">{children}</main>
            </SidebarInset>
        </SidebarProvider>
    );
}
