import qz from 'qz-tray';

let connected = false;
let connectPromise = null;
let securityConfigured = false;

export function getMeta(name) {
    return document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function setupQzSecurity() {
    if (securityConfigured) {
        return;
    }

    securityConfigured = true;

    qz.security.setCertificatePromise((resolve, reject) => {
        fetch('/qz/certificate', { cache: 'no-store', credentials: 'same-origin' })
            .then((response) => (response.ok ? response.text() : Promise.reject(response.statusText)))
            .then(resolve)
            .catch(reject);
    });

    qz.security.setSignatureAlgorithm('SHA512');
    qz.security.setSignaturePromise((toSign) => (resolve, reject) => {
        fetch('/qz/sign', {
            method: 'POST',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'text/plain',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: toSign,
        })
            .then((response) => (response.ok ? response.text() : Promise.reject(response.statusText)))
            .then(resolve)
            .catch(reject);
    });
}

export function isQzConnected() {
    return connected;
}

export async function ensureQzConnected() {
    setupQzSecurity();

    if (connected) {
        return true;
    }

    if (!connectPromise) {
        connectPromise = qz.websocket
            .connect()
            .then(() => {
                connected = true;

                return true;
            })
            .catch(() => {
                connectPromise = null;

                return false;
            });
    }

    return connectPromise;
}

export async function listPrinters() {
    if (! (await ensureQzConnected())) {
        return [];
    }

    const printers = await qz.printers.find();

    return Array.isArray(printers) ? printers : [];
}

export async function printHtml(printerName, html, paperWidthMm = 80) {
    if (! printerName) {
        throw new Error('لم يتم اختيار طابعة.');
    }

    if (! (await ensureQzConnected())) {
        throw new Error('QZ Tray غير متصل — ثبّته على هذا الجهاز وشغّله.');
    }

    const config = qz.configs.create(printerName);
    const pageWidthInches = paperWidthMm / 25.4;

    const data = [
        {
            type: 'pixel',
            format: 'html',
            flavor: 'plain',
            data: html,
            options: {
                pageWidth: pageWidthInches,
                pageHeight: 11,
            },
        },
    ];

    await qz.print(config, data);
}

export async function printUrl(printerName, url, paperWidthMm = 80) {
    const silentUrl = url.includes('?') ? `${url}&silent=1` : `${url}?silent=1`;
    const response = await fetch(silentUrl, { credentials: 'same-origin' });

    if (! response.ok) {
        throw new Error('تعذّر تحميل محتوى الطباعة.');
    }

    const html = await response.text();

    await printHtml(printerName, html, paperWidthMm);
}
