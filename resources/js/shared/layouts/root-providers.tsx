import { usePage } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import type { FlashToast } from '@/types/ui';

type FlashBag = {
    toast?: FlashToast | null;
    success?: string | null;
    error?: string | null;
    message?: string | null;
};

/**
 * Outermost Inertia layout — keeps Toaster inside PageContext (safe for usePage/hooks).
 */
export default function RootProviders({ children }: { children: React.ReactNode }) {
    const { dir, flash } = usePage().props as {
        dir?: string;
        flash?: FlashBag | null;
    };
    const resolvedDir = dir === 'ltr' ? 'ltr' : 'rtl';

    return (
        <>
            {children}
            <Toaster dir={resolvedDir} initialFlash={flash ?? null} />
        </>
    );
}
