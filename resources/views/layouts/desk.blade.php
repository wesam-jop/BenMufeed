<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'بن مفيد — الفواتير' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=el-messiri:400,500,600,700|tajawal:300,400,500,700&display=swap" rel="stylesheet">
    @vite('resources/js/desk.js')
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="pos-body inv-body">
    {{ $slot }}
    @livewireScripts
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('searchableSelect', (config) => ({
                open: false,
                search: '',
                value: config.value,
                options: config.options ?? [],
                placeholder: config.placeholder ?? 'اختر...',
                searchPlaceholder: config.searchPlaceholder ?? 'ابحث...',
                clearable: config.clearable ?? true,
                selectedLabel: '',
                dropdownStyle: 'display:none;',
                scrollHandler: null,
                resizeHandler: null,
                outsideClickHandler: null,

                init() {
                    this.syncSearchFromValue();

                    this.$watch('value', () => {
                        this.syncSearchFromValue();
                    });

                    this.scrollHandler = () => {
                        if (this.open) {
                            this.positionDropdown();
                        }
                    };

                    this.resizeHandler = () => {
                        if (this.open) {
                            this.positionDropdown();
                        }
                    };

                    window.addEventListener('scroll', this.scrollHandler, true);
                    window.addEventListener('resize', this.resizeHandler);
                },

                destroy() {
                    window.removeEventListener('scroll', this.scrollHandler, true);
                    window.removeEventListener('resize', this.resizeHandler);
                    this.detachOutsideClick();
                },

                attachOutsideClick() {
                    this.detachOutsideClick();

                    this.outsideClickHandler = (event) => {
                        const control = this.$refs.control;
                        const dropdown = this.$refs.dropdown;

                        if (control?.contains(event.target) || dropdown?.contains(event.target)) {
                            return;
                        }

                        this.close();
                    };

                    setTimeout(() => document.addEventListener('click', this.outsideClickHandler, true), 0);
                },

                detachOutsideClick() {
                    if (this.outsideClickHandler) {
                        document.removeEventListener('click', this.outsideClickHandler, true);
                        this.outsideClickHandler = null;
                    }
                },

                syncSearchFromValue() {
                    const option = this.findOption(this.value);

                    if (option) {
                        this.selectedLabel = option.label;
                        if (! this.open) {
                            this.search = option.label;
                        }
                    } else if (this.value === null || this.value === '') {
                        this.selectedLabel = '';
                        if (! this.open) {
                            this.search = '';
                        }
                    }
                },

                findOption(value) {
                    return this.options.find((option) => String(option.value) === String(value));
                },

                get filteredOptions() {
                    const query = this.search.trim().toLowerCase();
                    let results = this.options;

                    if (query !== '') {
                        const terms = query.split(/\s+/).filter(Boolean);

                        results = this.options.filter((option) => {
                            const haystack = `${option.label} ${option.search ?? ''} ${option.meta ?? ''}`.toLowerCase();

                            return terms.every((term) => haystack.includes(term));
                        });
                    } else if (results.length > 10) {
                        results = results.slice(0, 10);
                    }

                    return results;
                },

                get showSearchHint() {
                    return this.search.trim() === '' && this.options.length > 10;
                },

                get searchHint() {
                    return `يعرض ${Math.min(10, this.options.length)} من ${this.options.length} — اكتب للبحث...`;
                },

                positionDropdown() {
                    const control = this.$refs.control;

                    if (! control) {
                        return;
                    }

                    const rect = control.getBoundingClientRect();
                    const width = Math.max(rect.width, 260);
                    const maxHeight = 240;
                    const spaceBelow = window.innerHeight - rect.bottom - 12;
                    const spaceAbove = rect.top - 12;
                    const openUp = spaceBelow < 180 && spaceAbove > spaceBelow;
                    const height = Math.min(maxHeight, openUp ? spaceAbove : spaceBelow);
                    const top = openUp ? rect.top - height - 4 : rect.bottom + 4;
                    const left = Math.min(Math.max(8, rect.left), window.innerWidth - width - 8);

                    this.dropdownStyle = [
                        'position:fixed',
                        `top:${Math.max(8, top)}px`,
                        `left:${left}px`,
                        `width:${width}px`,
                        `max-height:${height}px`,
                        'z-index:9999',
                    ].join(';');
                },

                openDropdown() {
                    this.open = true;
                    this.$nextTick(() => {
                        this.positionDropdown();
                        this.attachOutsideClick();

                        if (this.selectedLabel) {
                            this.search = '';
                        }
                    });
                },

                close() {
                    this.open = false;
                    this.dropdownStyle = 'display:none;';
                    this.detachOutsideClick();
                    this.syncSearchFromValue();
                },

                toggle() {
                    this.open ? this.close() : this.openDropdown();
                },

                onSearchInput() {
                    this.open = true;
                    this.$nextTick(() => {
                        this.positionDropdown();
                        this.attachOutsideClick();
                    });
                },

                selectOption(option) {
                    this.value = option.value;
                    this.selectedLabel = option.label;
                    this.search = option.label;
                    this.close();
                },

                clearSelection() {
                    this.value = null;
                    this.selectedLabel = '';
                    this.search = '';
                    this.close();
                },
            }));
        });
    </script>
</body>
</html>
