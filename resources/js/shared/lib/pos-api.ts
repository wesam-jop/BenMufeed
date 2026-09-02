function csrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function extractErrorMessage(data: unknown): string {
    if (typeof data !== 'object' || data === null) {
        return 'حدث خطأ غير متوقع';
    }

    const payload = data as { message?: string; errors?: Record<string, string[]> };

    if (payload.errors) {
        for (const messages of Object.values(payload.errors)) {
            if (Array.isArray(messages) && messages[0]) {
                return messages[0];
            }
        }
    }

    if (payload.message && typeof payload.message === 'string') {
        return payload.message;
    }

    return 'حدث خطأ غير متوقع';
}

async function posRequest<T>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await fetch(url, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers ?? {}),
        },
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(extractErrorMessage(data));
    }

    return data as T;
}

export type PosOrderItem = {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: number;
    product?: { name: string } | null;
};

export type PosOrder = {
    id: number;
    order_number: string;
    type: string;
    status: string;
    total: number;
    delivery_fee?: number;
    items?: PosOrderItem[];
};

export type PosTableSession = {
    id: number;
    table_id: number;
    status: string;
};

export async function openTableOrder(tableId: number): Promise<{
    table: { id: number; name: string };
    session: PosTableSession;
    order: PosOrder | null;
}> {
    return posRequest(`/pos/tables/${tableId}/order`);
}

export async function createOrder(payload: Record<string, unknown>): Promise<{ order: PosOrder }> {
    return posRequest('/pos/orders', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function addOrderItem(
    orderId: number,
    payload: { product_id: number; quantity: number },
): Promise<{ order: PosOrder }> {
    return posRequest(`/pos/orders/${orderId}/items`, {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function updateOrderItem(
    orderId: number,
    itemId: number,
    payload: { quantity: number },
): Promise<{ order: PosOrder }> {
    return posRequest(`/pos/orders/${orderId}/items/${itemId}`, {
        method: 'PATCH',
        body: JSON.stringify(payload),
    });
}

export async function removeOrderItem(orderId: number, itemId: number): Promise<{ order: PosOrder }> {
    return posRequest(`/pos/orders/${orderId}/items/${itemId}`, {
        method: 'DELETE',
    });
}

export async function sendOrderToKitchen(orderId: number): Promise<{ order: PosOrder }> {
    return posRequest(`/pos/orders/${orderId}/send-to-kitchen`, { method: 'POST' });
}

export async function completeOrder(
    orderId: number,
    payload: {
        warehouse_id: number;
        payments: Array<{ method: string; amount: number; reference?: string }>;
    },
): Promise<{ invoice: { id: number; invoice_number?: string } }> {
    return posRequest(`/pos/orders/${orderId}/complete`, {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function acceptDeliveryOrder(orderId: number): Promise<{ message?: string }> {
    return posRequest(`/pos/delivery-orders/${orderId}/accept`, { method: 'POST' });
}

export async function rejectDeliveryOrder(
    orderId: number,
    reason?: string,
): Promise<{ message?: string }> {
    return posRequest(`/pos/delivery-orders/${orderId}/reject`, {
        method: 'POST',
        body: JSON.stringify({ reason }),
    });
}

export async function acceptTableOrder(orderId: number): Promise<{ message?: string }> {
    return posRequest(`/pos/table-orders/${orderId}/accept`, { method: 'POST' });
}

export async function rejectTableOrder(
    orderId: number,
    reason?: string,
): Promise<{ message?: string }> {
    return posRequest(`/pos/table-orders/${orderId}/reject`, {
        method: 'POST',
        body: JSON.stringify({ reason }),
    });
}
