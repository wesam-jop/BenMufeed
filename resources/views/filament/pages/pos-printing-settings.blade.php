<x-filament-panels::page>
    <div class="mb-6 rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-950/40">
        <p class="text-sm font-semibold text-gray-950 dark:text-white">ربط الطابعات من جهاز الكاشير</p>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            لاكتشاف الطابعات المتصلة بالكمبيوتر (USB / شبكة) يجب فتح صفحة الربط على <strong>نفس الجهاز</strong> الذي توصّلت عليه الطابعة، مع تشغيل QZ Tray.
        </p>
        <a
            href="{{ route('pos.printing') }}"
            target="_blank"
            class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
        >
            فتح معالج ربط الطابعات
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            حفظ إعدادات الفاتورة والتوصيل
        </x-filament::button>
    </form>

    <x-filament::section class="mt-8" heading="روابط التشغيل">
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <p>
                <strong>ربط الطابعات:</strong>
                <a href="{{ route('pos.printing') }}" target="_blank" class="text-primary-600 underline">{{ route('pos.printing') }}</a>
            </p>
            <p>
                <strong>سجل الطباعة:</strong>
                <a href="{{ url('/pos/print-log') }}" target="_blank" class="text-primary-600 underline">{{ url('/pos/print-log') }}</a>
            </p>
            <p>
                <strong>كاشير:</strong>
                <a href="{{ url('/pos') }}" target="_blank" class="text-primary-600 underline">{{ url('/pos') }}</a>
                — الطباعة التلقائية لتذاكر المطبخ تعمل من هنا (مع QZ Tray + طابعة المطبخ)
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
