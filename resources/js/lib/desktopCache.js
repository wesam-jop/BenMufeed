/**
 * ذاكرة محلية لتطبيق سطح المكتب (Electron) — أسرع من localStorage للبيانات الكبيرة.
 */
export async function desktopCacheGet(key, namespace = 'apiCache') {
    if (typeof window === 'undefined' || !window.benMufeedDesktop?.cacheGet) {
        return null;
    }

    return window.benMufeedDesktop.cacheGet(namespace, key);
}

export async function desktopCacheSet(key, value, ttlMs, namespace = 'apiCache') {
    if (typeof window === 'undefined' || !window.benMufeedDesktop?.cacheSet) {
        return false;
    }

    return window.benMufeedDesktop.cacheSet(namespace, key, value, ttlMs);
}

export function isBenMufeedDesktop() {
    return typeof window !== 'undefined' && Boolean(window.benMufeedDesktop?.isDesktop);
}

/**
 * جلب JSON مع تخزين مؤقت في Electron (أو fetch عادي في المتصفح).
 */
export async function fetchWithDesktopCache(url, { key, ttlMs = 3600000, init } = {}) {
    const cacheKey = key || url;

    if (isBenMufeedDesktop()) {
        const cached = await desktopCacheGet(cacheKey);
        if (cached !== null && cached !== undefined) {
            return cached;
        }
    }

    const response = await fetch(url, init);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();

    if (isBenMufeedDesktop()) {
        await desktopCacheSet(cacheKey, data, ttlMs);
    }

    return data;
}
