import { formatPhone, splitCountryKey } from '@/lib/phoneCountries';

/**
 * @param {number|null|undefined} variantId
 * @param {Array<{ product_addon_id: number, quantity?: number }>} addons
 */
export function buildCartLineKey(productId, variantId = null, addons = []) {
    const addonPart = [...addons]
        .map((a) => `${a.product_addon_id}:${Math.max(1, Number(a.quantity || 1))}`)
        .sort()
        .join(',');

    return `${productId}:${variantId || 0}:${addonPart}`;
}

/**
 * @param {{ id: number, name: string, price: number, cost?: number|null, is_default?: boolean }} variant
 * @param {Array<{ id: number, name: string, price: number, max_quantity?: number }>} addons
 * @param {Record<number, number>} addonQty
 */
export function calculateUnitPrice(variants, addons, selectedVariantId, addonQty) {
    const variant = variants.find((v) => v.id === selectedVariantId)
        ?? variants.find((v) => v.is_default)
        ?? variants[0];

    let total = variant ? Number(variant.price) : 0;

    for (const addon of addons) {
        const qty = Math.min(addon.max_quantity || 1, Math.max(0, Number(addonQty[addon.id] || 0)));

        if (qty > 0) {
            total += Number(addon.price) * qty;
        }
    }

    return { unitPrice: total, variant };
}

/**
 * @param {string|null|undefined} variantName
 * @param {Array<{ name: string, quantity: number }>} selectedAddons
 */
export function buildOptionsLabel(variantName, selectedAddons) {
    const parts = [];

    if (variantName) {
        parts.push(variantName);
    }

    for (const addon of selectedAddons) {
        parts.push(addon.quantity > 1 ? `${addon.name} ×${addon.quantity}` : addon.name);
    }

    return parts.length ? parts.join(' + ') : null;
}

/**
 * @param {string} productName
 * @param {string|null|undefined} optionsLabel
 */
export function buildDisplayName(productName, optionsLabel) {
    return optionsLabel ? `${productName} (${optionsLabel})` : productName;
}

export { formatPhone, splitCountryKey, previewPhone } from '@/lib/phoneCountries';
