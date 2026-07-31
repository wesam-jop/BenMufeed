import { useMemo, useState } from 'react';
import { buildCartItemFromOptions } from '@/lib/cart';
import { calculateUnitPrice } from '@/lib/productOptions';
import { formatPrice } from '@/lib/formatPrice';

const btnPrimary =
    'rounded-full bg-store-secondary px-6 py-2.5 text-sm font-bold text-store-primary shadow-sm transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40';

/**
 * @param {{
 *   product: { id: number, name: string, price: number, variants?: Array, addons?: Array, has_options?: boolean },
 *   symbol?: string,
 *   onConfirm: (item: ReturnType<typeof buildCartItemFromOptions>, quantity: number) => void,
 *   onCancel?: () => void,
 *   submitLabel?: string,
 * }} props
 */
export default function ProductOptionsPicker({ product, symbol = '$', onConfirm, onCancel, submitLabel = 'أضف للطلب' }) {
    const variants = product.variants || [];
    const addons = product.addons || [];
    const defaultVariant = variants.find((v) => v.is_default) || variants[0];

    const [selectedVariantId, setSelectedVariantId] = useState(defaultVariant?.id ?? null);
    const [addonQty, setAddonQty] = useState({});
    const [quantity, setQuantity] = useState(1);

    const { unitPrice } = useMemo(
        () => calculateUnitPrice(variants, addons, selectedVariantId, addonQty),
        [variants, addons, selectedVariantId, addonQty],
    );

    const requiresVariant = variants.length > 0;
    const canSubmit = !requiresVariant || selectedVariantId != null;

    const toggleAddon = (addon) => {
        setAddonQty((prev) => {
            const current = Number(prev[addon.id] || 0);
            const max = addon.max_quantity || 1;

            if (current >= max) {
                const next = { ...prev };
                delete next[addon.id];

                return next;
            }

            return { ...prev, [addon.id]: current + 1 };
        });
    };

    const submit = () => {
        if (!canSubmit) {
            return;
        }

        onConfirm(buildCartItemFromOptions(product, selectedVariantId, addonQty), quantity);
    };

    return (
        <div className="space-y-5">
            {product.is_delivery_available === false ? (
                <div className="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    هذا الصنف متاح داخل الصالة أو سفري فقط، وغير متاح ضمن طلبات التوصيل.
                </div>
            ) : null}
            {variants.length ? (
                <div>
                    <p className="text-sm font-bold text-store-primary">اختر النوع *</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                        {variants.map((variant) => (
                            <button
                                key={variant.id}
                                type="button"
                                onClick={() => setSelectedVariantId(variant.id)}
                                className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
                                    selectedVariantId === variant.id
                                        ? 'border-store-secondary bg-store-secondary text-store-primary'
                                        : 'border-store-primary/15 bg-white text-store-primary hover:border-store-secondary/50'
                                }`}
                            >
                                {variant.name} — {formatPrice(variant.price, symbol)}
                            </button>
                        ))}
                    </div>
                </div>
            ) : null}

            {addons.length ? (
                <div>
                    <p className="text-sm font-bold text-store-primary">إضافات (اختياري)</p>
                    <div className="mt-2 space-y-2">
                        {addons.map((addon) => {
                            const qty = Number(addonQty[addon.id] || 0);
                            const active = qty > 0;

                            return (
                                <button
                                    key={addon.id}
                                    type="button"
                                    onClick={() => toggleAddon(addon)}
                                    className={`flex w-full items-center justify-between rounded-xl border px-4 py-3 text-sm transition ${
                                        active
                                            ? 'border-store-secondary bg-store-secondary/15 text-store-primary'
                                            : 'border-store-primary/10 bg-white text-store-primary hover:border-store-secondary/40'
                                    }`}
                                >
                                    <span className="font-semibold">
                                        {addon.name}
                                        {active && qty > 1 ? ` ×${qty}` : ''}
                                    </span>
                                    <span className="font-bold text-store-secondary">
                                        +{formatPrice(addon.price * (active ? qty : 1), symbol)}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            ) : null}

            <div className="flex items-center justify-between rounded-xl border border-store-primary/10 bg-store-primary/[0.03] px-4 py-3">
                <div>
                    <p className="text-xs text-store-muted">السعر للقطعة</p>
                    <p className="text-xl font-bold text-store-secondary">{formatPrice(unitPrice, symbol)}</p>
                </div>
                <div className="flex items-center gap-2">
                    <label htmlFor="options-qty" className="text-xs text-store-muted">
                        الكمية
                    </label>
                    <input
                        id="options-qty"
                        type="number"
                        min={1}
                        max={999}
                        value={quantity}
                        onChange={(e) => setQuantity(Math.min(999, Math.max(1, Number(e.target.value) || 1)))}
                        className="w-20 rounded-lg border border-store-primary/15 px-2 py-1 text-sm"
                    />
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                <button type="button" disabled={!canSubmit} onClick={submit} className={btnPrimary}>
                    {submitLabel}
                </button>
                {onCancel ? (
                    <button
                        type="button"
                        onClick={onCancel}
                        className="rounded-full border border-store-primary/20 px-6 py-2.5 text-sm font-bold text-store-primary"
                    >
                        إلغاء
                    </button>
                ) : null}
            </div>
        </div>
    );
}
