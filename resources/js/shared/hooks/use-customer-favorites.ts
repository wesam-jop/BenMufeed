import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import type { Auth } from '@/types/auth';

export function useCustomerFavorites() {
    const { t } = useI18n();
    const { auth, favoriteProductIds = [] } = usePage<{
        auth: Auth;
        favoriteProductIds?: number[];
    }>().props;

    const favoriteIds = new Set(favoriteProductIds);
    const isLoggedIn = Boolean(auth.customer);

    const isFavorite = (productId: number) => favoriteIds.has(productId);

    const toggleFavorite = (productId: number) => {
        if (!isLoggedIn) {
            toast.info(t('toast.login_for_favorites', 'Sign in or complete an order to use favorites'));
            router.visit('/auth/login');

            return;
        }

        if (isFavorite(productId)) {
            router.delete(`/customer/favorites/${productId}`, { preserveScroll: true });

            return;
        }

        router.post(
            '/customer/favorites',
            { product_id: productId },
            { preserveScroll: true },
        );
    };

    return {
        isFavorite,
        toggleFavorite,
        isLoggedIn,
    };
}
