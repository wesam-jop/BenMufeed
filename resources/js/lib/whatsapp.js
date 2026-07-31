/** @param {string | null | undefined} phone */
export function whatsappDigits(phone) {
    return String(phone || '').replace(/\D+/g, '');
}

/** @param {string | null | undefined} phone */
export function whatsappHref(phone) {
    const digits = whatsappDigits(phone);

    return digits ? `https://wa.me/${digits}` : null;
}
