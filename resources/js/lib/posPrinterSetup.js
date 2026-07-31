import {
    ensureQzConnected,
    isQzConnected,
    listPrinters,
    printHtml,
    setupQzSecurity,
} from './qzPrint.js';

const TEST_HTML = (title, printerName) => `
<div style="font-family:Tahoma,sans-serif;width:72mm;padding:8px;font-size:12px;">
  <div style="text-align:center;font-weight:bold;font-size:14px;">بن مفيد</div>
  <div style="text-align:center;margin:8px 0;">${title}</div>
  <div style="border-top:1px dashed #000;margin:8px 0;padding-top:8px;">
    <div>الطابعة: ${printerName}</div>
    <div>${new Date().toLocaleString('ar-SY')}</div>
  </div>
  <div style="text-align:center;margin-top:12px;font-size:11px;">اختبار ناجح</div>
</div>`;

export function registerPosPrinterSetup() {
    Alpine.data('posPrinterSetup', () => ({
        wire: null,
        printers: [],
        kitchenPrinter: '',
        cashierPrinter: '',
        paperWidthMm: 80,
        qzStatus: 'idle',
        statusMessage: '',
        testing: null,

        initFromWire(livewire) {
            this.wire = livewire;

            if (livewire?.kitchen_printer_name !== undefined) {
                this.kitchenPrinter = livewire.kitchen_printer_name || '';
                this.cashierPrinter = livewire.cashier_printer_name || '';
                this.paperWidthMm = livewire.receipt_paper_width_mm || 80;
            } else if (livewire?.get) {
                this.kitchenPrinter = livewire.get('kitchen_printer_name') || '';
                this.cashierPrinter = livewire.get('cashier_printer_name') || '';
                this.paperWidthMm = livewire.get('receipt_paper_width_mm') || 80;
            }

            this.refreshPrinters();
        },

        get qzConnected() {
            return this.qzStatus === 'connected';
        },

        async refreshPrinters() {
            this.qzStatus = 'loading';
            this.statusMessage = 'جاري الاتصال ببرنامج الطباعة على هذا الجهاز...';

            setupQzSecurity();

            const connected = await ensureQzConnected();

            if (! connected) {
                this.qzStatus = 'error';
                this.statusMessage =
                    'برنامج QZ Tray غير شغّال. ثبّته على نفس جهاز الكاشير ثم أعد فتح هذه الصفحة.';
                this.printers = [];

                return;
            }

            try {
                this.printers = await listPrinters();
                this.qzStatus = 'connected';
                this.statusMessage = this.printers.length
                    ? `تم العثور على ${this.printers.length} طابعة متصلة بهذا الجهاز.`
                    : 'متصل — لم يُعثر على طابعات. تأكد من توصيل USB وتشغيل الطابعة.';
            } catch {
                this.qzStatus = 'error';
                this.statusMessage = 'تعذّر قراءة قائمة الطابعات.';
                this.printers = [];
            }
        },

        syncToWire() {
            if (! this.wire) {
                return;
            }

            if (this.wire.set) {
                this.wire.set('kitchen_printer_name', this.kitchenPrinter || null);
                this.wire.set('cashier_printer_name', this.cashierPrinter || null);
            }
        },

        assignKitchen(name) {
            this.kitchenPrinter = name;
            this.syncToWire();
        },

        assignCashier(name) {
            this.cashierPrinter = name;
            this.syncToWire();
        },

        isKitchen(name) {
            return this.kitchenPrinter === name;
        },

        isCashier(name) {
            return this.cashierPrinter === name;
        },

        async testPrint(role) {
            const name = role === 'kitchen' ? this.kitchenPrinter : this.cashierPrinter;
            const label = role === 'kitchen' ? 'اختبار — مطبخ' : 'اختبار — كاشير';

            if (! name) {
                this.statusMessage = 'اختر الطابعة أولاً ثم اضغط اختبار.';

                return;
            }

            this.testing = role;
            this.statusMessage = `جاري طباعة ${label}...`;

            try {
                await printHtml(name, TEST_HTML(label, name), this.paperWidthMm);
                this.statusMessage = `تم إرسال ${label} إلى: ${name}`;
            } catch (error) {
                this.statusMessage =
                    error instanceof Error ? error.message : 'فشلت طباعة الاختبار.';
            } finally {
                this.testing = null;
            }
        },
    }));
}

export { isQzConnected, ensureQzConnected };
