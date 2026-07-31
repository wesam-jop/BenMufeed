<div class="pos-shell">
    <x-pos.header :open-tables-count="0" />

    <main class="pos-page-main pos-print-setup-page">
        @if (session('print_setup_ok'))
            <div class="pos-toast pos-toast-success pos-print-setup-flash" wire:key="flash-ok">
                <span>{{ session('print_setup_ok') }}</span>
            </div>
        @endif

        <header class="pos-print-setup-hero">
            <p class="pos-setup-eyebrow">إعداد الطباعة</p>
            <h1 class="pos-page-title">ربط الطابعات على هذا الجهاز</h1>
            <a href="https://qz.io/download/" target="_blank" rel="noreferrer" class="text-store-secondary underline">QZ Tray</a>
            <p class="pos-page-sub">
                الطابعات تُكتشف من Windows مباشرة. عيّن طابعة للكاشير وأخرى للمطبخ — عند كل طلب يُرسل أمر طباعة تلقائياً بدون نافذة المتصفح.
            </p>
        </header>

        @vite('resources/js/pos.js')

        <form class="pos-print-setup-form" x-on:submit.prevent>
            <div
                class="pos-print-setup-panel"
                x-data="posPrinterSetup()"
                x-init="initFromWire($wire)"
                @print-settings.window="initFromWire($wire)"
            >
                <div class="pos-print-qz-bar" :class="{ 'pos-print-qz-bar--ok': qzConnected, 'pos-print-qz-bar--err': qzStatus === 'error' }">
                    <div class="pos-print-qz-bar__status">
                        <span class="pos-print-qz-dot" :class="qzStatus"></span>
                        <div>
                            <p class="pos-print-qz-title">برنامج الربط (QZ Tray)</p>
                            <p class="pos-print-qz-msg" x-text="statusMessage"></p>
                        </div>
                    </div>
                    <button type="button" class="pos-history-btn" x-on:click="refreshPrinters()" x-bind:disabled="qzStatus === 'loading'">
                        <span x-show="qzStatus !== 'loading'">تحديث قائمة الطابعات</span>
                        <span x-show="qzStatus === 'loading'">جاري البحث...</span>
                    </button>
                </div>

                <template x-if="qzStatus === 'error'">
                    <div class="pos-print-qz-help">
                        <p class="font-bold text-store-primary">خطوات التثبيت (مرة واحدة على هذا الكمبيوتر):</p>
                        <ol class="mt-2 list-decimal space-y-1 ps-5 text-sm leading-relaxed text-store-muted">
                            <li>حمّل <a href="https://qz.io/download/" target="_blank" rel="noreferrer" class="text-store-secondary underline">QZ Tray</a> وثبّته.</li>
                            <li>شغّله — يظهر أيقونة بجانب الساعة.</li>
                            <li>أعد تحميل هذه الصفحة واضغط «تحديث قائمة الطابعات».</li>
                            <li>عند أول اتصال وافق على السماح للموقع (شهادة الأمان تلقائية من السيرفر).</li>
                        </ol>
                    </div>
                </template>

                <div class="pos-print-assign-summary">
                    <div class="pos-print-assign-box">
                        <span class="pos-print-assign-label">طابعة الكاشير</span>
                        <span class="pos-print-assign-value" x-text="cashierPrinter || '— لم تُعيَّن بعد'"></span>
                        <button type="button" class="pos-history-btn pos-print-test-btn" x-on:click="testPrint('cashier')" x-show="cashierPrinter" x-bind:disabled="testing === 'cashier'">
                            اختبار طباعة
                        </button>
                    </div>
                    <div class="pos-print-assign-box pos-print-assign-box--kitchen">
                        <span class="pos-print-assign-label">طابعة المطبخ</span>
                        <span class="pos-print-assign-value" x-text="kitchenPrinter || '— لم تُعيَّن بعد'"></span>
                        <button type="button" class="pos-history-btn pos-print-test-btn" x-on:click="testPrint('kitchen')" x-show="kitchenPrinter" x-bind:disabled="testing === 'kitchen'">
                            اختبار طباعة
                        </button>
                    </div>
                </div>

                <div class="pos-print-printer-list" x-show="printers.length > 0">
                    <p class="pos-print-list-title">الطابعات المتصلة بهذا الجهاز</p>
                    <ul class="pos-print-printer-grid">
                        <template x-for="printer in printers" :key="printer">
                            <li class="pos-print-printer-card">
                                <div class="pos-print-printer-card__icon" aria-hidden="true">
                                    <svg class="pos-icon pos-icon-lg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0a42.418 42.418 0 00-4.82 0m4.82 0v2.25m0-12.75h.008v.008H12v-.008zm0 3h.008v.008H12V9.75zm-2.25 4.5h4.5a2.25 2.25 0 002.25-2.25v-1.5a2.25 2.25 0 00-2.25-2.25h-4.5A2.25 2.25 0 006 14.25v1.5a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                                <p class="pos-print-printer-card__name" x-text="printer"></p>
                                <div class="pos-print-printer-card__actions">
                                    <button
                                        type="button"
                                        class="pos-print-assign-btn"
                                        :class="{ 'pos-print-assign-btn--active': isCashier(printer) }"
                                        x-on:click="assignCashier(printer)"
                                    >
                                        كاشير
                                    </button>
                                    <button
                                        type="button"
                                        class="pos-print-assign-btn pos-print-assign-btn--kitchen"
                                        :class="{ 'pos-print-assign-btn--active': isKitchen(printer) }"
                                        x-on:click="assignKitchen(printer)"
                                    >
                                        مطبخ
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="pos-print-options">
                    <p class="pos-print-list-title">سلوك الطباعة التلقائية</p>
                    <label class="pos-print-toggle">
                        <input type="checkbox" wire:model="auto_print_kitchen">
                        <span>طباعة تذكرة المطبخ تلقائياً عند قبول طلب الموقع أو إرسال للمطبخ</span>
                    </label>
                    <label class="pos-print-toggle">
                        <input type="checkbox" wire:model="auto_print_cashier">
                        <span>طباعة إيصال الكاشير تلقائياً (بيع سفري / قبول طلب / إغلاق طاولة)</span>
                    </label>
                    <label class="pos-print-toggle">
                        <input type="checkbox" wire:model="kitchen_alert_sound">
                        <span>صوت تنبيه عند طلب جديد</span>
                    </label>
                    <div class="pos-print-paper">
                        <label class="pos-print-assign-label" for="paper-width">عرض الورق الحراري</label>
                        <select id="paper-width" wire:model="receipt_paper_width_mm" class="pos-print-paper-select">
                            <option value="58">58 مم</option>
                            <option value="80">80 مم</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="pos-print-setup-actions">
                <a href="{{ route('pos.cashier') }}" class="pos-history-btn">العودة للكاشير</a>
                <button
                    type="button"
                    class="pos-checkout-btn pos-print-save-btn"
                    x-on:click="syncToWire(); $wire.save()"
                >
                    حفظ الإعدادات
                </button>
            </div>
        </form>

        <section class="pos-page-card pos-print-setup-note">
            <p class="text-sm leading-relaxed text-store-muted">
                <strong class="text-store-primary">ملاحظة:</strong>
                جهاز الكاشير يحتاج QZ Tray. عيّن طابعة الكاشير وطابعة المطبخ من هنا — كل طلب جديد يُطبَع تلقائياً على طابعة المطبخ (بدون شاشة مطبخ).
            </p>
        </section>
    </main>
</div>
