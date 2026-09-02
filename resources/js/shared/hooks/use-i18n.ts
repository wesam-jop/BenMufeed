import { router, usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

type SharedLocale = {
    locale: string;
    dir: 'rtl' | 'ltr';
    availableLocales: string[];
    translations: Record<string, string>;
};

export function useI18n() {
    const page = usePage();
    const locale = (page.props.locale as string) || 'ar';
    const dir = ((page.props.dir as string) || 'rtl') as 'rtl' | 'ltr';
    const translations = (page.props.translations as Record<string, string>) || {};
    const availableLocales = (page.props.availableLocales as string[]) || ['ar', 'en'];

    const t = useCallback(
        (key: string, fallback?: string) => {
            return translations[key] ?? fallback ?? key;
        },
        [translations],
    );

    const setLocale = useCallback((next: string) => {
        router.post(`/locale/${next}`, {}, { preserveScroll: true });
    }, []);

    return useMemo(
        () => ({
            locale,
            dir,
            isRtl: dir === 'rtl',
            availableLocales,
            translations,
            t,
            setLocale,
        }),
        [locale, dir, availableLocales, translations, t, setLocale],
    );
}

export type { SharedLocale };
