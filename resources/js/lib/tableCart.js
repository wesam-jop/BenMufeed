import {
    buildCartLineKey,
    buildDisplayName,
    buildOptionsLabel,
    calculateUnitPrice,
} from '@/lib/productOptions';

// سلة مستقلة لواجهة الطاولة — منفصلة عن سلة التوصيل/السفري.
const STORAGE_KEY = 'bm_table_cart_v1';
const ORDER_SESSION_KEY = 'bm_table_order_session_v1';

function normalizeAddons(addons) {
    if (!Array.isArray(addons)) {
        return [];
    }

    return addons
        .filter((a) => a && a.product_addon_id)
        .map((a) => ({
            product_addon_id: Number(a.product_addon_id),
            name: String(a.name || ''),
            price: Number(a.price || 0),
            quantity: Math.min(10, Math.max(1, Number(a.quantity || 1))),
        }));
}

function normalize(raw) {
    if (!raw || !Array.isArray(raw.items)) {
        return { items: [] };
    }

    return {
        items: raw.items
            .filter((i) => i && i.product_id && i.quantity > 0)
            .map((i) => ({
                line_key: String(i.line_key || buildCartLineKey(i.product_id, i.product_variant_id, i.addons)),
                product_id: Number(i.product_id),
                product_variant_id: i.product_variant_id ? Number(i.product_variant_id) : null,
                variant_name: i.variant_name || null,
                addons: normalizeAddons(i.addons),
                name: String(i.name || ''),
                price: Number(i.price || 0),
                image_url: i.image_url || null,
                quantity: Math.min(999, Math.max(1, Number(i.quantity || 1))),
            })),
    };
}

export function readTableCart() {
    if (typeof window === 'undefined') {
        return { items: [] };
    }

    try {
        return normalize(JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}'));
    } catch {
        return { items: [] };
    }
}

export function writeTableCart(cart) {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(normalize(cart)));
    window.dispatchEvent(new CustomEvent('bm:table-cart-updated'));
}

/** يبني صف سلة من منتج + الخيارات المختارة. */
export function buildTableCartItem(product, selectedVariantId, addonQuantities) {
    const variants = product.variants || [];
    const addons = product.addons || [];
    const { unitPrice, variant } = calculateUnitPrice(variants, addons, selectedVariantId, addonQuantities);

    const selectedAddons = addons
        .map((addon) => ({
            product_addon_id: addon.id,
            name: addon.name,
            price: addon.price,
            quantity: Math.min(addon.max_quantity || 1, Math.max(0, Number(addonQuantities[addon.id] || 0))),
        }))
        .filter((a) => a.quantity > 0);

    const optionsLabel = buildOptionsLabel(variant?.name, selectedAddons);

    return {
        product_id: product.id,
        product_variant_id: variant?.id ?? null,
        variant_name: variant?.name ?? null,
        addons: selectedAddons,
        name: buildDisplayName(product.name, optionsLabel),
        price: unitPrice,
        image_url: product.image_url,
    };
}

export function addTableCartItem(item, quantity = 1) {
    const addons = normalizeAddons(item.addons);
    const lineKey = buildCartLineKey(item.product_id, item.product_variant_id, addons);
    const cart = readTableCart();
    const idx = cart.items.findIndex((i) => i.line_key === lineKey);

    const row = {
        line_key: lineKey,
        product_id: item.product_id,
        product_variant_id: item.product_variant_id ?? null,
        variant_name: item.variant_name ?? null,
        addons,
        name: item.name,
        price: Number(item.price || 0),
        image_url: item.image_url ?? null,
        quantity,
    };

    if (idx === -1) {
        cart.items.push(row);
    } else {
        cart.items[idx] = {
            ...cart.items[idx],
            ...row,
            quantity: Math.min(999, cart.items[idx].quantity + quantity),
        };
    }

    writeTableCart(cart);

    if (typeof window !== 'undefined') {
        window.dispatchEvent(
            new CustomEvent('bm:table-cart-item-added', {
                detail: { name: row.name, quantity: row.quantity },
            }),
        );
    }

    return cart;
}

export function setTableCartQuantity(lineKey, quantity) {
    const cart = readTableCart();
    const idx = cart.items.findIndex((i) => i.line_key === lineKey);

    if (idx === -1) {
        return cart;
    }

    if (quantity <= 0) {
        cart.items.splice(idx, 1);
    } else {
        cart.items[idx].quantity = Math.min(999, quantity);
    }

    writeTableCart(cart);

    return cart;
}

export function removeTableCartItem(lineKey) {
    const cart = readTableCart();
    cart.items = cart.items.filter((i) => i.line_key !== lineKey);
    writeTableCart(cart);

    return cart;
}

export function clearTableCart() {
    writeTableCart({ items: [] });
}

export function tableCartCount(cart) {
    const c = cart ?? readTableCart();

    return c.items.reduce((sum, i) => sum + (Number(i.quantity) || 0), 0);
}

export function tableCartSubtotal(cart) {
    const c = cart ?? readTableCart();

    return c.items.reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.quantity) || 0), 0);
}

export function readTableOrderSession() {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.localStorage.getItem(ORDER_SESSION_KEY) || null;
}

export function saveTableOrderSession(token) {
    if (typeof window === 'undefined' || !token) {
        return;
    }

    window.localStorage.setItem(ORDER_SESSION_KEY, String(token));
}

export function clearTableOrderSession() {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.removeItem(ORDER_SESSION_KEY);
}

export { buildCartLineKey };
