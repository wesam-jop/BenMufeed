function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function parseJson(response) {
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message =
            data?.message ||
            data?.errors?.otp?.[0] ||
            data?.errors?.customer_phone_local?.[0] ||
            data?.errors?.customer_name?.[0] ||
            data?.errors?.address_label?.[0] ||
            data?.errors?.location_note?.[0] ||
            'حدث خطأ — حاول مرة أخرى.';

        const error = new Error(message);
        error.status = response.status;
        error.errors = data?.errors || {};
        throw error;
    }

    return data;
}

export async function sendStorefrontOtp({ name, phoneCountry, phoneLocal, phone }) {
    const response = await fetch('/api/account/otp/send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            customer_name: name || null,
            customer_phone: phone || null,
            customer_phone_country: phoneCountry,
            customer_phone_local: phoneLocal,
        }),
    });

    return parseJson(response);
}

export async function verifyStorefrontOtp({ name, phoneCountry, phoneLocal, phone, otp, serviceType }) {
    const response = await fetch('/api/account/otp/verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            customer_name: name,
            customer_phone: phone || null,
            customer_phone_country: phoneCountry,
            customer_phone_local: phoneLocal,
            otp,
            service_type: serviceType,
        }),
    });

    return parseJson(response);
}

export async function updateStorefrontProfile(name) {
    const response = await fetch('/api/account/profile', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({ name }),
    });

    return parseJson(response);
}

export async function createSavedAddress({ label, locationNote, latitude, longitude }) {
    const response = await fetch('/api/account/addresses', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            label: label.trim(),
            location_note: locationNote.trim(),
            latitude,
            longitude,
        }),
    });

    return parseJson(response);
}

export async function deleteSavedAddress(addressId) {
    const response = await fetch(`/api/account/addresses/${addressId}`, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
    });

    return parseJson(response);
}
