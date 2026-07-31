import {
    buildCartLineKey,
    buildDisplayName,
    buildOptionsLabel,
    calculateUnitPrice,
} from '@/lib/productOptions';

const STORAGE_KEY = 'industrial_store_cart_v2';
const TABLE_TOKEN_KEY = 'industrial_store_table_token_v1';
const TABLE_SESSION_KEY = 'industrial_store_table_session_v1';
const TABLE_CONTEXT_KEY = 'industrial_store_table_context_v1';

function normalizeAddonSelections(addons) {
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

function normalizeCart(raw) {
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
                addons: normalizeAddonSelections(i.addons),
                slug: String(i.slug || ''),
                name: String(i.name || ''),
                price: Number(i.price || 0),
                is_delivery_available: i.is_delivery_available !== false,
                compare_at_price:
                    i.compare_at_price === null || i.compare_at_price === undefined
                        ? null
                        : Number(i.compare_at_price),
                image_url: i.image_url || null,
                quantity: Math.min(999, Math.max(1, Number(i.quantity || 1))),
            })),
    };
}

export function readCart() {
    if (typeof window === 'undefined') {
        return { items: [] };
    }

    try {
        const raw = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}');

        return normalizeCart(raw);
    } catch {
        return { items: [] };
    }
}

export function writeCart(cart) {
    if (typeof window === 'undefined') {
        return;
    }

    const normalized = normalizeCart(cart);
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
    window.dispatchEvent(new CustomEvent('kinju:cart-updated'));
}

/** @param {{ product_id: number, slug?: string, name: string, price?: number, compare_at_price?: number|null, image_url?: string|null, product_variant_id?: number|null, variant_name?: string|null, addons?: Array<{product_addon_id:number,name?:string,price?:number,quantity?:number}>, variants?: Array, product_addons?: Array }} item */
export function upsertCartItem(item, quantity = 1) {
    const addons = normalizeAddonSelections(item.addons);
    const lineKey = buildCartLineKey(item.product_id, item.product_variant_id, addons);
    const cart = readCart();
    const idx = cart.items.findIndex((i) => i.line_key === lineKey);

    const row = {
        line_key: lineKey,
        product_id: item.product_id,
        product_variant_id: item.product_variant_id ?? null,
        variant_name: item.variant_name ?? null,
        addons,
        slug: item.slug || '',
        name: item.name,
        price: Number(item.price || 0),
        is_delivery_available: item.is_delivery_available !== false,
        compare_at_price: item.compare_at_price ?? null,
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

    writeCart(cart);

    if (typeof window !== 'undefined') {
        window.dispatchEvent(
            new CustomEvent('kinju:cart-item-added', {
                detail: { name: row.name, quantity: row.quantity },
            }),
        );
    }

    return cart;
}

/** Build cart row from product detail options */
export function buildCartItemFromOptions(product, selectedVariantId, addonQuantities) {
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
        slug: product.slug,
        name: buildDisplayName(product.name, optionsLabel),
        price: unitPrice,
        is_delivery_available: product.is_delivery_available !== false,
        compare_at_price: product.compare_at_price,
        image_url: product.image_url,
    };
}

export function setQuantity(lineKey, quantity) {
    const cart = readCart();
    const idx = cart.items.findIndex((i) => i.line_key === lineKey);

    if (idx === -1) {
        return cart;
    }

    if (quantity <= 0) {
        cart.items.splice(idx, 1);
    } else {
        cart.items[idx].quantity = Math.min(999, quantity);
    }

    writeCart(cart);

    return cart;
}

export function removeItem(lineKey) {
    const cart = readCart();
    cart.items = cart.items.filter((i) => i.line_key !== lineKey);
    writeCart(cart);

    return cart;
}

export function cartTotalQuantity(cart) {
    const c = cart ?? readCart();

    return c.items.reduce((sum, i) => sum + (Number(i.quantity) || 0), 0);
}

export function cartSubtotal(cart) {
    const c = cart ?? readCart();

    return c.items.reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.quantity) || 0), 0);
}

export function clearCart() {
    writeCart({ items: [] });
}

export function setTableToken(token) {
    if (typeof window === 'undefined') {
        return;
    }

    if (token) {
        window.localStorage.setItem(TABLE_TOKEN_KEY, String(token));
    } else {
        window.localStorage.removeItem(TABLE_TOKEN_KEY);
    }
}

export function readTableToken() {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.localStorage.getItem(TABLE_TOKEN_KEY);
}

export function clearTableToken() {
    setTableToken(null);
}

export function readTableSession() {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = JSON.parse(window.localStorage.getItem(TABLE_SESSION_KEY) || 'null');

        if (!raw?.qrToken || !raw?.session) {
            return null;
        }

        return {
            qrToken: String(raw.qrToken),
            session: String(raw.session),
        };
    } catch {
        return null;
    }
}

export function saveTableSession({ qrToken, session }) {
    if (typeof window === 'undefined' || !qrToken || !session) {
        return;
    }

    window.localStorage.setItem(
        TABLE_SESSION_KEY,
        JSON.stringify({
            qrToken: String(qrToken),
            session: String(session),
        }),
    );
}

export function clearTableSession() {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.removeItem(TABLE_SESSION_KEY);
}

/**
 * @typedef {{ qrToken: string, number: number, label: string, locked: boolean, session?: string|null, scanSession?: string|null, lockedAt: number }} TableContext
 */

/** @returns {TableContext|null} */
export function readTableContext() {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = JSON.parse(window.localStorage.getItem(TABLE_CONTEXT_KEY) || 'null');

        if (!raw?.qrToken) {
            return null;
        }

        return {
            qrToken: String(raw.qrToken),
            number: Number(raw.number || 0),
            label: String(raw.label || ''),
            locked: Boolean(raw.locked),
            session: raw.session ? String(raw.session) : null,
            scanSession: raw.scanSession ? String(raw.scanSession) : null,
            lockedAt: Number(raw.lockedAt || 0),
        };
    } catch {
        return null;
    }
}

/** @param {Omit<TableContext, 'lockedAt'> & { lockedAt?: number }} context */
export function saveTableContext(context) {
    if (typeof window === 'undefined' || !context?.qrToken) {
        return;
    }

    const payload = {
        qrToken: String(context.qrToken),
        number: Number(context.number || 0),
        label: String(context.label || ''),
        locked: Boolean(context.locked),
        session: context.session ? String(context.session) : null,
        scanSession: context.scanSession ? String(context.scanSession) : null,
        lockedAt: context.lockedAt || Date.now(),
    };

    window.localStorage.setItem(TABLE_CONTEXT_KEY, JSON.stringify(payload));
    setTableToken(payload.qrToken);

    if (payload.session) {
        saveTableSession({ qrToken: payload.qrToken, session: payload.session });
    }

    window.dispatchEvent(new CustomEvent('kinju:table-context-updated'));
}

export function clearTableContext() {
    clearTableToken();
    clearTableSession();

    if (typeof window !== 'undefined') {
        window.localStorage.removeItem(TABLE_CONTEXT_KEY);
        window.dispatchEvent(new CustomEvent('kinju:table-context-updated'));
    }
}

export function isTableOrderingLocked() {
    const context = readTableContext();

    return context !== null && context.locked === true;
}

export async function resolveTableToken(token, { scanSession = null, freshScan = false } = {}) {
    const stored = readTableSession();
    const params = new URLSearchParams();

    if (freshScan) {
        params.set('fresh_scan', '1');
    } else if (scanSession) {
        params.set('scan_session', scanSession);
    }

    if (stored?.qrToken === token && stored.session) {
        params.set('session', stored.session);
    }

    const query = params.toString();
    let url = `/api/tables/resolve/${encodeURIComponent(token)}`;

    if (query) {
        url += `?${query}`;
    }

    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        const error = new Error(data.message || 'invalid_table_token');
        error.code = data.scan_session_expired
            ? 'scan_session_expired'
            : data.scan_session_required
              ? 'scan_session_required'
              : 'invalid_table_token';
        error.payload = data;
        throw error;
    }

    return response.json();
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export async function syncCartWithServer() {
    const cart = readCart();

    if (!cart.items.length || typeof window === 'undefined') {
        return { cart, priceChanged: false, removedUnavailable: false };
    }

    const response = await fetch('/cart/sync', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ product_ids: cart.items.map((i) => i.product_id) }),
    });

    if (!response.ok) {
        return { cart, priceChanged: false, removedUnavailable: false };
    }

    const data = await response.json();
    const byId = Object.fromEntries((data.items || []).map((row) => [row.product_id, row]));

    let priceChanged = false;
    let removedUnavailable = false;
    const nextItems = [];

    for (const item of cart.items) {
        const server = byId[item.product_id];

        if (!server?.available) {
            removedUnavailable = true;
            continue;
        }

        const updated = {
            ...item,
            slug: server.slug || item.slug,
            compare_at_price: server.compare_at_price,
            image_url: server.image_url ?? item.image_url,
            is_delivery_available: server.is_delivery_available !== false,
        };

        if (updated.compare_at_price !== item.compare_at_price) {
            priceChanged = true;
        }

        nextItems.push(updated);
    }

    if (priceChanged || removedUnavailable) {
        writeCart({ items: nextItems });
    }

    return {
        cart: readCart(),
        priceChanged,
        removedUnavailable,
    };
}

export { buildCartLineKey };
