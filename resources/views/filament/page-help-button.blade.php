@if ($helpUrl)
    <a
        href="{{ $helpUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        class="fi-color-gray fi-btn fi-btn-size-md fi-btn-outlined inline-grid shrink-0 grid-flow-col items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-gray-950/10 transition hover:bg-gray-50 dark:ring-white/20 dark:hover:bg-white/5"
        title="يفتح شرحاً مفصلاً لكل حقل في هذه الصفحة"
    >
        <x-filament::icon icon="heroicon-o-question-mark-circle" class="h-5 w-5" />
        <span>شرح الصفحة — اضغط لفهم البيانات</span>
    </a>
@endif
