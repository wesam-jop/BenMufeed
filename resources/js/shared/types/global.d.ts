import type { Auth } from '@/types/auth';
import type { Branch } from '@/types/auth';
import type { FlashMessages } from '@/types/admin';
import type { SharedBusiness } from '@/components/domain/BusinessBrand';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            business: SharedBusiness;
            locale: string;
            dir: 'rtl' | 'ltr';
            availableLocales: string[];
            translations: Record<string, string>;
            auth: Auth;
            currentBranch: Branch | null;
            branches: Branch[];
            flash: FlashMessages;
            sidebarOpen: boolean;
            favoriteProductIds?: number[];
            [key: string]: unknown;
        };
    }
}
