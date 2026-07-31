/**
 * POS / Kitchen: تنبيهات صوتية وطباعة مباشرة عبر QZ Tray + سجل الطباعة.
 */

import '../css/desk.css';

import {
    ensureQzConnected,
    getMeta,
    isQzConnected,
    listPrinters,
    printUrl,
    setupQzSecurity,
} from './lib/qzPrint.js';
import { registerPosPrinterSetup } from './lib/posPrinterSetup.js';

const printedKitchenTickets = new Set();
const printedCashierOrders = new Set();

setupQzSecurity();
registerPosPrinterSetup();

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function playPrintFailAlert() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'square';
        osc.frequency.value = 220;
        gain.gain.value = 0.12;
        osc.start();
        setTimeout(() => {
            osc.stop();
            ctx.close();
        }, 400);
    } catch {
        // ignore
    }
}

function playTone(ctx, frequency, startAt, duration, type = 'sine', volume = 0.12) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type;
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(0, startAt);
    gain.gain.linearRampToValueAtTime(volume, startAt + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.001, startAt + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(startAt);
    osc.stop(startAt + duration + 0.05);
}

function playAlert(kind) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;

        if (kind === 'website-delivery') {
            playTone(ctx, 523.25, now, 0.18, 'sine', 0.14);
            playTone(ctx, 659.25, now + 0.16, 0.18, 'sine', 0.14);
            playTone(ctx, 783.99, now + 0.32, 0.28, 'sine', 0.16);
            setTimeout(() => ctx.close(), 900);
        } else if (kind === 'website') {
            playTone(ctx, 440, now, 0.2, 'sine', 0.13);
            playTone(ctx, 554.37, now + 0.14, 0.22, 'sine', 0.13);
            playTone(ctx, 659.25, now + 0.3, 0.26, 'triangle', 0.11);
            setTimeout(() => ctx.close(), 800);
        } else if (kind === 'append') {
            playTone(ctx, 880, now, 0.12, 'sine', 0.12);
            setTimeout(() => ctx.close(), 250);
        } else {
            playTone(ctx, 660, now, 0.16, 'sine', 0.12);
            playTone(ctx, 784, now + 0.12, 0.2, 'sine', 0.1);
            setTimeout(() => ctx.close(), 450);
        }
    } catch {
        // ignore audio errors
    }
}

function ensureBrowserNotificationPermission() {
    if (!('Notification' in window) || Notification.permission !== 'default') {
        return;
    }

    Notification.requestPermission().catch(() => {});
}

function showBrowserNotification({ title, body, tag, url }) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    try {
        const notification = new Notification(title || 'بن مفيد', {
            body: body || '',
            tag: tag || 'pos-alert',
            icon: '/favicon.ico',
            lang: 'ar',
            dir: 'rtl',
        });

        notification.onclick = () => {
            window.focus();
            notification.close();

            if (url) {
                window.location.href = url;
            }
        };
    } catch {
        // ignore
    }
}

function paperWidthMm() {
    return parseInt(getMeta('pos-paper-width-mm'), 10) || 80;
}

function showPrintToast(message, isError = true) {
    const existing = document.getElementById('pos-print-toast');

    if (existing) {
        existing.remove();
    }

    const toast = document.createElement('div');
    toast.id = 'pos-print-toast';
    toast.className = isError ? 'pos-toast pos-toast-error pos-print-toast' : 'pos-toast pos-toast-success pos-print-toast';
    toast.innerHTML = `<span>${message}</span><button type="button" class="pos-toast-close" aria-label="إغلاق">×</button>`;
    toast.querySelector('.pos-toast-close')?.addEventListener('click', () => toast.remove());
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 8000);
}

async function reportJob(printJobId, action, message = '') {
    if (! printJobId) {
        return;
    }

    const url = `/print/jobs/${printJobId}/${action}`;
    const options = {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
        },
    };

    if (action === 'failed') {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify({ message });
    }

    try {
        await fetch(url, options);
    } catch {
        // ignore reporting errors
    }
}

async function runPrintJob({ url, printerName, printJobId, dedupeKey, dedupeSet }) {
    if (dedupeKey && dedupeSet?.has(dedupeKey)) {
        return true;
    }

    if (! printerName) {
        const msg = 'اختر طابعة من إعدادات الطباعة (/admin/pos-printing).';
        showPrintToast(msg);
        await reportJob(printJobId, 'failed', msg);

        return false;
    }

    await reportJob(printJobId, 'printing');

    try {
        await printUrl(printerName, url, paperWidthMm());
        await reportJob(printJobId, 'succeeded');

        if (dedupeKey && dedupeSet) {
            dedupeSet.add(dedupeKey);
        }

        return true;
    } catch (error) {
        const message = error instanceof Error ? error.message : 'فشلت الطباعة المباشرة.';
        showPrintToast(`${message} — راجع سجل الطباعة لإعادة التشغيل.`);
        playPrintFailAlert();
        await reportJob(printJobId, 'failed', message);
        updateFailedPrintBadge();

        return false;
    }
}

async function queueManualJob(endpoint) {
    const response = await fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
        },
    });

    if (! response.ok) {
        throw new Error('تعذّر إنشاء أمر الطباعة.');
    }

    return response.json();
}

async function printKitchenTicket(ticketId, printJobId = null) {
    if (! ticketId) {
        return false;
    }

    const key = `kitchen-${ticketId}`;

    if (! printJobId && printedKitchenTickets.has(key)) {
        return true;
    }

    let url = `/print/kitchen-ticket/${ticketId}`;
    let printerName = getMeta('pos-kitchen-printer');
    let jobId = printJobId;

    if (! jobId) {
        try {
            const payload = await queueManualJob(`/print/jobs/kitchen/${ticketId}`);
            jobId = payload.printJobId;
            url = payload.url || url;
            printerName = payload.printer || printerName;
        } catch (error) {
            showPrintToast(error instanceof Error ? error.message : 'تعذّر بدء الطباعة.');

            return false;
        }
    }

    return runPrintJob({
        url,
        printerName,
        printJobId: jobId,
        dedupeKey: printJobId ? null : key,
        dedupeSet: printJobId ? null : printedKitchenTickets,
    });
}

async function printCashierReceipt(orderId, printJobId = null, allowDuplicate = false) {
    if (! orderId) {
        return false;
    }

    const key = `cashier-${orderId}`;

    if (! printJobId && ! allowDuplicate && printedCashierOrders.has(key)) {
        return true;
    }

    let url = `/print/cashier-receipt/${orderId}`;
    let printerName = getMeta('pos-cashier-printer');
    let jobId = printJobId;

    if (! jobId) {
        try {
            const payload = await queueManualJob(`/print/jobs/cashier/${orderId}`);
            jobId = payload.printJobId;
            url = payload.url || url;
            printerName = payload.printer || printerName;
        } catch (error) {
            showPrintToast(error instanceof Error ? error.message : 'تعذّر بدء الطباعة.');

            return false;
        }
    }

    const ok = await runPrintJob({
        url,
        printerName,
        printJobId: jobId,
        dedupeKey: allowDuplicate || printJobId ? null : key,
        dedupeSet: allowDuplicate || printJobId ? null : printedCashierOrders,
    });

    if (ok && allowDuplicate) {
        printedCashierOrders.add(key);
    }

    return ok;
}

window.benMufeedPrint = {
    kitchenTicket: (ticketId) => printKitchenTicket(ticketId),
    cashierReceipt: (orderId) => printCashierReceipt(orderId, null, true),
    refreshPrinters: listPrinters,
    ensureConnected: ensureQzConnected,
    isConnected: isQzConnected,
};

async function fetchFailedPrintCount() {
    try {
        const response = await fetch('/api/print/failed-count', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (! response.ok) {
            return 0;
        }

        const data = await response.json();

        return Number(data.count) || 0;
    } catch {
        return 0;
    }
}

function updateFailedPrintBadge() {
    const link = document.querySelector('[data-pos-print-log-link]');

    if (! link) {
        return;
    }

    fetchFailedPrintCount().then((count) => {
        let badge = link.querySelector('[data-print-fail-badge]');

        if (count <= 0) {
            badge?.remove();

            return;
        }

        if (! badge) {
            badge = document.createElement('span');
            badge.dataset.printFailBadge = '1';
            badge.className = 'absolute -end-1 -top-1 flex min-h-[1rem] min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-0.5 text-[9px] font-bold text-white';
            link.querySelector('.relative')?.appendChild(badge) || link.appendChild(badge);
        }

        badge.textContent = count > 99 ? '99+' : String(count);
    });
}

let lastKnownFailedPrintCount = 0;

async function pollFailedPrints() {
    const count = await fetchFailedPrintCount();

    if (lastKnownFailedPrintCount === 0) {
        lastKnownFailedPrintCount = count;
    } else if (count > lastKnownFailedPrintCount) {
        playPrintFailAlert();
        showPrintToast(`فشلت ${count - lastKnownFailedPrintCount} عملية طباعة — راجع سجل الطباعة.`);
        lastKnownFailedPrintCount = count;
    } else {
        lastKnownFailedPrintCount = count;
    }
    updateFailedPrintBadge();
}

const dispatchedPendingPrintJobs = new Set();

async function pollPendingPrintJobs() {
    try {
        const response = await fetch('/api/print/pending-jobs', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (! response.ok) {
            return;
        }

        const payload = await response.json();

        for (const job of payload.jobs || []) {
            if (! job.printJobId || dispatchedPendingPrintJobs.has(job.printJobId)) {
                continue;
            }

            dispatchedPendingPrintJobs.add(job.printJobId);

            if (job.kind === 'kitchen_ticket' && job.ticketId) {
                await printKitchenTicket(job.ticketId, job.printJobId);
            } else if (job.kind === 'cashier_receipt' && job.orderId) {
                await printCashierReceipt(job.orderId, job.printJobId, true);
            }
        }
    } catch {
        // ignore
    }
}

if (document.querySelector('meta[name="pos-kitchen-printer"]')) {
    pollFailedPrints();
    setInterval(pollFailedPrints, 30000);
    pollPendingPrintJobs();
    setInterval(pollPendingPrintJobs, 12000);
}

if (document.querySelector('meta[name="pos-kitchen-printer"]')) {
    ensureBrowserNotificationPermission();
    document.body.addEventListener(
        'click',
        () => ensureBrowserNotificationPermission(),
        { once: true },
    );
}

document.addEventListener('livewire:init', () => {
    Livewire.on('pos-play-alert', ({ kind }) => {
        playAlert(kind || 'new');
    });

    Livewire.on('pos-browser-notify', (payload) => {
        showBrowserNotification(payload);
    });

    Livewire.on('print-kitchen-ticket', ({ ticketId, printJobId }) => {
        if (printJobId) {
            dispatchedPendingPrintJobs.add(printJobId);
        }

        printKitchenTicket(ticketId, printJobId ?? null);
    });

    Livewire.on('print-cashier-receipt', ({ orderId, printJobId }) => {
        if (printJobId) {
            dispatchedPendingPrintJobs.add(printJobId);
        }

        printCashierReceipt(orderId, printJobId ?? null, false);
    });
});
