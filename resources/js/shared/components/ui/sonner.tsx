import { useFlashToast } from '@/hooks/use-flash-toast';
import { useAppearance } from '@/hooks/use-appearance';
import { Toaster as Sonner, type ToasterProps } from 'sonner';
import type { FlashToast } from '@/types/ui';

type FlashBag = {
    toast?: FlashToast | null;
    success?: string | null;
    error?: string | null;
    message?: string | null;
};

type AppToasterProps = ToasterProps & {
    dir?: 'rtl' | 'ltr';
    initialFlash?: FlashBag | null;
};

function Toaster({ dir = 'rtl', initialFlash = null, ...props }: AppToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast(initialFlash);

    return (
        <Sonner
            theme={appearance}
            className="toaster group"
            position={dir === 'rtl' ? 'top-left' : 'top-right'}
            richColors
            closeButton
            dir={dir}
            toastOptions={{
                classNames: {
                    toast: 'border shadow-lg font-sans',
                    title: 'text-sm font-medium',
                    description: 'text-sm opacity-90',
                },
            }}
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
