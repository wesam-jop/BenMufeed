<x-filament-panels::page>
    @if ($help)
        <div class="admin-help-doc space-y-6">
            <div class="rounded-xl border border-primary-200 bg-primary-50/80 p-5 dark:border-primary-500/30 dark:bg-primary-500/10">
                <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-200">
                    {{ $help['intro'] }}
                </p>
            </div>

            @foreach ($help['sections'] as $section)
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <header class="border-b border-gray-100 px-5 py-4 dark:border-white/10">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $section['heading'] }}
                        </h2>
                        @if (! empty($section['description']))
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {{ $section['description'] }}
                            </p>
                        @endif
                    </header>
                    <dl class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($section['fields'] as $field)
                            <div class="px-5 py-4">
                                <dt class="text-sm font-bold text-primary-700 dark:text-primary-400">
                                    {{ $field['label'] }}
                                </dt>
                                <dd class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                    {{ $field['text'] }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            @if (! empty($help['tips']))
                <section class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">نصائح مهمة</h2>
                    <ul class="mt-3 list-disc space-y-2 ps-5 text-sm text-amber-900/90 dark:text-amber-100/90">
                        @foreach ($help['tips'] as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
