/**
 * @param {string} dial
 * @param {string} local
 */
export function formatPhone(dial, local) {
    const normalized = String(local || '').replace(/\D+/g, '').replace(/^0+/, '');

    if (!normalized || !dial) {
        return '';
    }

    return `${String(dial).replace(/\D+/g, '')}${normalized}`;
}

/**
 * @param {string} dial
 * @param {string} local
 */
export function previewPhone(dial, local) {
    const full = formatPhone(dial, local);

    if (!full) {
        return 'أدخل رقم الجوال بدون الصفر في البداية (مثال: 9xxxxxxxx).';
    }

    return `الرقم الكامل: +${full}`;
}

/**
 * @param {string} dialIso e.g. 963|SY
 */
export function splitCountryKey(dialIso) {
    const key = String(dialIso || '');

    if (key.includes('|')) {
        const [dial, iso] = key.split('|', 2);

        return { dial, iso };
    }

    return { dial: key, iso: '' };
}
