import { Link, router, usePage } from '@inertiajs/react';
import { ChevronsUpDown, LogOut, Store, User } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import type { Auth } from '@/types/auth';

export function NavCustomer() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const customer = auth.customer;

    if (!customer) {
        return null;
    }

    const logout = () => {
        router.post('/auth/logout');
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                        >
                            <div className="flex size-8 items-center justify-center rounded-lg bg-amber-100 text-amber-900">
                                <User className="size-4" />
                            </div>
                            <div className="grid flex-1 text-right text-sm leading-tight">
                                <span className="truncate font-semibold">{customer.name ?? 'زبون'}</span>
                                <span className="truncate text-xs text-muted-foreground" dir="ltr">
                                    {customer.phone}
                                </span>
                            </div>
                            <ChevronsUpDown className="ms-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'}
                    >
                        <DropdownMenuItem asChild>
                            <Link href="/customer/profile" className="cursor-pointer">
                                <User className="size-4" />
                                الملف الشخصي
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href="/" className="cursor-pointer">
                                <Store className="size-4" />
                                المتجر
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={logout} className="cursor-pointer text-destructive">
                            <LogOut className="size-4" />
                            تسجيل الخروج
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
