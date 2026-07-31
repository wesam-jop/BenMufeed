export function readTableTokenFromUrl() {
    if (typeof window === 'undefined') {
        return null;
    }

    return new URLSearchParams(window.location.search).get('table_token');
}

export function isFreshTableScanFromUrl() {
    if (typeof window === 'undefined') {
        return false;
    }

    return new URLSearchParams(window.location.search).get('table_scan') === 'fresh';
}

export function stripTableTokenFromUrl() {
    if (typeof window === 'undefined') {
        return;
    }

    const url = new URL(window.location.href);

    if (!url.searchParams.has('table_token') && !url.searchParams.has('table_scan')) {
        return;
    }

    url.searchParams.delete('table_token');
    url.searchParams.delete('table_scan');
    const next = `${url.pathname}${url.search}${url.hash}`;
    window.history.replaceState({}, '', next);
}

/** رسائل أخطاء تحديد الموقع (لخريطة التوصيل). */
export function geolocationErrorMessage(error) {
    if (!error) {
        return 'تعذّر تحديد موقعك.';
    }

    if (error.code === 1 || error.message === 'User denied Geolocation') {
        return 'يجب السماح بالموقع (GPS) لتحديد موقع التوصيل.';
    }

    if (error.code === 2) {
        return 'تعذّر تحديد موقعك — تحقق من GPS.';
    }

    if (error.code === 3) {
        return 'انتهت مهلة تحديد الموقع — حاول مجدداً.';
    }

    if (error.message === 'geolocation_unsupported') {
        return 'المتصفح لا يدعم تحديد الموقع.';
    }

    return 'تعذّر التحقق من موقعك.';
}
