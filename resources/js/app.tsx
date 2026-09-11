import { createInertiaApp, router } from '@inertiajs/react';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AdminLayout from '@/layouts/admin-layout';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import CustomerLayout from '@/layouts/customer-layout';
import RootProviders from '@/layouts/root-providers';
import SettingsLayout from '@/layouts/settings/layout';
import { setMoneyConfig, type MoneyConfig } from '@/lib/format';
import type { ComponentType, ReactNode } from 'react';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

type LayoutComponent = ComponentType<{ children: ReactNode }>;

function syncMoneyFromPageProps(props: Record<string, unknown> | undefined): void {
    if (props?.money) {
        setMoneyConfig(props.money as MoneyConfig);
    }
}

function withRootProviders(
    layout: LayoutComponent | LayoutComponent[] | null | undefined,
): LayoutComponent | LayoutComponent[] {
    if (!layout) {
        return RootProviders;
    }

    if (Array.isArray(layout)) {
        return [RootProviders, ...layout];
    }

    return [RootProviders, layout];
}

router.on('success', (event) => {
    syncMoneyFromPageProps(event.detail?.page?.props as Record<string, unknown>);
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Pages live under modules/{area}/pages — keep Inertia names like admin/products/index
    pages: {
        path: './modules',
        lazy: false,
        transform: (name: string) => {
            const areas = [
                'admin',
                'pos',
                'kitchen',
                'customer',
                'storefront',
                'auth',
                'settings',
            ];
            const slash = name.indexOf('/');
            const head = slash === -1 ? name : name.slice(0, slash);
            const rest = slash === -1 ? '' : name.slice(slash + 1);

            if (areas.includes(head)) {
                return rest ? `${head}/pages/${rest}` : `${head}/pages/index`;
            }

            // Root pages (welcome, dashboard, …)
            return `shared/pages/${name}`;
        },
    },
    layout: (name) => {
        let layout: LayoutComponent | LayoutComponent[] | null;

        switch (true) {
            case name === 'welcome':
                layout = null;
                break;
            case name.startsWith('auth/'):
                layout = AuthLayout;
                break;
            case name.startsWith('settings/'):
                layout = [AppLayout, SettingsLayout];
                break;
            case name.startsWith('admin/'):
                layout = AdminLayout;
                break;
            case name.startsWith('pos/'):
            case name.startsWith('kitchen/'):
            case name.startsWith('storefront/'):
            case name.startsWith('customer/auth/'):
            case name.startsWith('customer/password/'):
            case name === 'customer/qr-order':
                layout = null;
                break;
            case name.startsWith('customer/'):
                layout = CustomerLayout;
                break;
            default:
                layout = AppLayout;
                break;
        }

        return withRootProviders(layout);
    },
    strictMode: true,
    withApp(app, { page }) {
        syncMoneyFromPageProps(page?.props as Record<string, unknown> | undefined);
        const props = (page?.props ?? {}) as {
            dir?: string;
            locale?: string;
        };
        const dir = props.dir === 'ltr' ? 'ltr' : 'rtl';
        const lang = props.locale === 'en' ? 'en' : 'ar';

        return (
            <TooltipProvider delayDuration={0}>
                <div dir={dir} lang={lang} className="min-h-screen">
                    {app}
                </div>
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
