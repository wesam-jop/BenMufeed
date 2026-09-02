import { toast as sonner } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Sonner toasts with i18n keys (same visual style as dashboard flash toasts).
 */
export function useAppToast() {
    const { t } = useI18n();

    return {
        success: (key: string, replace?: Record<string, string | number>, fallback?: string) => {
            sonner.success(format(t(key, fallback ?? key), replace));
        },
        error: (key: string, replace?: Record<string, string | number>, fallback?: string) => {
            sonner.error(format(t(key, fallback ?? key), replace));
        },
        info: (key: string, replace?: Record<string, string | number>, fallback?: string) => {
            sonner.info(format(t(key, fallback ?? key), replace));
        },
        warning: (key: string, replace?: Record<string, string | number>, fallback?: string) => {
            sonner.warning(format(t(key, fallback ?? key), replace));
        },
        raw: sonner,
        t,
    };
}

function format(message: string, replace?: Record<string, string | number>): string {
    if (!replace) {
        return message;
    }

    return Object.entries(replace).reduce(
        (msg, [key, value]) => msg.replaceAll(`:${key}`, String(value)),
        message,
    );
}
