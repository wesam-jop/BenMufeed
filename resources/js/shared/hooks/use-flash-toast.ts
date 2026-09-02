import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

type FlashBag = {
    toast?: FlashToast | null;
    success?: string | null;
    error?: string | null;
    message?: string | null;
};

function showFlash(flash: FlashBag | null | undefined): void {
    if (!flash) {
        return;
    }

    if (flash.toast?.message) {
        const fn = toast[flash.toast.type] ?? toast.message;
        fn(flash.toast.message);

        return;
    }

    if (flash.success) {
        toast.success(flash.success);
    }

    if (flash.error) {
        toast.error(flash.error);
    }

    if (flash.message) {
        toast.message(flash.message);
    }
}

function flashFingerprint(flash: FlashBag | null | undefined): string {
    return JSON.stringify(flash ?? null);
}

/**
 * Global flash listener for Sonner.
 * Uses router events only — safe outside Inertia's usePage() tree (e.g. withApp Toaster).
 */
export function useFlashToast(initialFlash?: FlashBag | null): void {
    const lastShown = useRef<string | null>(null);

    useEffect(() => {
        if (!initialFlash) {
            return;
        }

        const fingerprint = flashFingerprint(initialFlash);

        if (fingerprint === lastShown.current) {
            return;
        }

        if (!initialFlash.toast && !initialFlash.success && !initialFlash.error && !initialFlash.message) {
            lastShown.current = fingerprint;

            return;
        }

        lastShown.current = fingerprint;
        showFlash(initialFlash);
    }, [initialFlash]);

    useEffect(() => {
        const offSuccess = router.on('success', (event) => {
            const flash = (event.detail?.page?.props?.flash ?? null) as FlashBag | null;
            const fingerprint = flashFingerprint(flash);

            if (!flash || fingerprint === lastShown.current) {
                return;
            }

            if (!flash.toast && !flash.success && !flash.error && !flash.message) {
                lastShown.current = fingerprint;

                return;
            }

            lastShown.current = fingerprint;
            showFlash(flash);
        });

        const offFlash = router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash as FlashBag | undefined;
            showFlash(flash);
        });

        return () => {
            offSuccess();
            offFlash();
        };
    }, []);
}
