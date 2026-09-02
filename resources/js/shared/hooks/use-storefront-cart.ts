import { router } from '@inertiajs/react';

export type CartItem = {
    product_id: number;
    name: string;
    price: number;
    image?: string | null;
    quantity: number;
};

export type CartSummary = {
    items: CartItem[];
    location_lat?: number | null;
    location_lng?: number | null;
    location_address?: string | null;
    location_notes?: string | null;
    count: number;
    subtotal: number;
};

export function useStorefrontCart() {
    const addItem = (productId: number, quantity = 1) => {
        router.post(
            '/cart/items',
            { product_id: productId, quantity },
            { preserveScroll: true },
        );
    };

    const updateQuantity = (productId: number, quantity: number) => {
        router.patch(
            `/cart/items/${productId}`,
            { quantity },
            { preserveScroll: true },
        );
    };

    const removeItem = (productId: number) => {
        router.delete(`/cart/items/${productId}`, { preserveScroll: true });
    };

    const updateLocation = (data: {
        location_lat?: number | null;
        location_lng?: number | null;
        location_address?: string;
        location_notes?: string;
    }) => {
        router.patch('/cart/location', data, { preserveScroll: true });
    };

    return { addItem, updateQuantity, removeItem, updateLocation };
}
