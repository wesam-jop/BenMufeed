/**
 * @param {number|string} amount
 * @param {string} [symbol='$']
 */
export function formatPrice(amount, symbol = '$') {
    const n = Number(amount);

    if (Number.isNaN(n)) {
        return `0 ${symbol}`;
    }

    return `${new Intl.NumberFormat('ar-SA', { maximumFractionDigits: 2 }).format(n)} ${symbol}`;
}
