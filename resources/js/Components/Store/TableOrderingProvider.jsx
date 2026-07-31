import { router, usePage } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import {
    clearTableContext,
    readTableContext,
    readTableSession,
    resolveTableToken,
    saveTableContext,
} from '@/lib/cart';
import {
    isFreshTableScanFromUrl,
    readTableTokenFromUrl,
    stripTableTokenFromUrl,
} from '@/lib/tableOrdering';

/** @type {import('react').Context<any>} */
const TableOrderingContext = createContext(null);

/** Must be used inside {@link StoreLayout} (or {@link withStoreLayout}). */
export function useTableOrdering() {
    const value = useContext(TableOrderingContext);

    if (!value) {
        throw new Error('useTableOrdering must be used within TableOrderingProvider');
    }

    return value;
}

export function TableOrderingProvider({ children }) {
    const { tableScanPolicy } = usePage().props;
    const [table, setTable] = useState(() => readTableContext());
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const activateToken = useCallback(
        async (token, { fromQr = false } = {}) => {
            if (!token) {
                return null;
            }

            setLoading(true);
            setError(null);

            const existing = readTableContext();
            const freshScan = fromQr || isFreshTableScanFromUrl();

            try {
                const info = await resolveTableToken(token, {
                    freshScan,
                    scanSession: freshScan ? null : existing?.scanSession || null,
                });

                if (!info.can_order) {
                    clearTableContext();
                    setTable(null);
                    setError(`الطاولة ${info.label} مفتوحة — لا يمكن الطلب من هذا الجهاز.`);
                    return null;
                }

                const orderSession = readTableSession();
                const context = {
                    qrToken: token,
                    number: info.number,
                    label: info.label,
                    locked: fromQr || freshScan || existing?.locked,
                    scanSession: info.scan_session,
                    session:
                        orderSession?.qrToken === token && orderSession.session
                            ? orderSession.session
                            : existing?.session || null,
                };

                saveTableContext(context);
                setTable(readTableContext());

                return info;
            } catch (err) {
                if (err.code === 'scan_session_expired' || err.code === 'scan_session_required') {
                    clearTableContext();
                    setTable(null);
                    setError('انتهت جلسة الطاولة — افتح رابط QR على الطاولة من كاميرا جوالك من جديد.');
                } else {
                    setError('رمز الطاولة غير صالح — افتح رابط QR من كاميرا جوالك.');
                }

                return null;
            } finally {
                setLoading(false);
            }
        },
        [],
    );

    const refresh = useCallback(async () => {
        const context = readTableContext();

        if (!context?.qrToken) {
            setTable(null);
            return null;
        }

        return activateToken(context.qrToken, { fromQr: false });
    }, [activateToken]);

    useEffect(() => {
        const onUpdate = () => setTable(readTableContext());
        window.addEventListener('kinju:table-context-updated', onUpdate);

        return () => window.removeEventListener('kinju:table-context-updated', onUpdate);
    }, []);

    useEffect(() => {
        const tokenFromUrl = readTableTokenFromUrl();

        if (!tokenFromUrl) {
            const existing = readTableContext();

            if (existing?.qrToken) {
                refresh();
            }

            return;
        }

        (async () => {
            await activateToken(tokenFromUrl, { fromQr: isFreshTableScanFromUrl() });
            stripTableTokenFromUrl();
        })();
    }, [activateToken, refresh]);

    useEffect(() => {
        if (!table?.qrToken) {
            return undefined;
        }

        const intervalMs = Math.min(
            120000,
            Math.max(60000, ((tableScanPolicy?.idle_minutes || 45) * 60 * 1000) / 2),
        );

        const interval = window.setInterval(() => {
            refresh();
        }, intervalMs);

        return () => window.clearInterval(interval);
    }, [table?.qrToken, refresh, tableScanPolicy?.idle_minutes]);

    const clear = useCallback(() => {
        clearTableContext();
        setTable(null);
        setError(null);
    }, []);

    const value = useMemo(
        () => ({
            active: Boolean(table?.qrToken),
            locked: Boolean(table?.locked),
            table,
            loading,
            error,
            refresh,
            clear,
            activateToken,
            scanPolicy: tableScanPolicy,
        }),
        [table, loading, error, refresh, clear, activateToken, tableScanPolicy],
    );

    return <TableOrderingContext.Provider value={value}>{children}</TableOrderingContext.Provider>;
}

export function navigateToMenu() {
    router.visit('/products');
}
