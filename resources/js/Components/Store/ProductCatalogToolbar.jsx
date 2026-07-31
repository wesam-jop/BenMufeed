const sortOptions = [
    { value: 'latest', label: 'الأحدث', hint: 'وصل حديثاً' },
    { value: 'oldest', label: 'الأقدم', hint: 'الأول في القائمة' },
    { value: 'price_asc', label: 'السعر ↑', hint: 'من الأقل' },
    { value: 'price_desc', label: 'السعر ↓', hint: 'من الأعلى' },
];

function IconSearch({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
            <circle cx="11" cy="11" r="7" />
            <path d="M20 20l-3-3" strokeLinecap="round" />
        </svg>
    );
}

function IconX({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
            <path d="M6 6l12 12M18 6L6 18" strokeLinecap="round" />
        </svg>
    );
}

function IconFilter({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
            <path d="M4 6h16M7 12h10M10 18h4" strokeLinecap="round" />
        </svg>
    );
}

/**
 * @param {{
 *   q: string,
 *   setQ: (v: string) => void,
 *   sort: string,
 *   setSort: (v: string) => void,
 *   category: string,
 *   setCategory: (slug: string) => void,
 *   categories: Array<{ slug: string, name: string, published_products_count?: number }>,
 *   loading?: boolean,
 *   meta?: { total?: number, from?: number|null, to?: number|null },
 *   hasActiveFilters?: boolean,
 *   onClear?: () => void,
 *   title?: string,
 *   subtitle?: string,
 *   hideCategoryFilter?: boolean,
 * }} props
 */
export default function ProductCatalogToolbar({
    q,
    setQ,
    sort,
    setSort,
    category,
    setCategory,
    categories = [],
    loading = false,
    meta = {},
    hasActiveFilters = false,
    onClear,
    title = 'قائمة الطعام',
    subtitle,
    hideCategoryFilter = false,
}) {
    const total = meta.total ?? 0;
    const activeCategory = categories.find((c) => c.slug === category);

    return (
        <section className="store-card overflow-hidden">
            <div className="border-b border-store-border bg-restaurant-hero px-5 py-6 text-store-surface sm:px-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="font-display text-h2 sm:text-h1">{title}</h1>
                        <p className="mt-1 text-body text-store-surface/75">
                            {subtitle ??
                                (loading ? 'جاري البحث…' : total > 0 ? `${total} طبق متاح` : 'لا توجد نتائج بعد')}
                        </p>
                    </div>
                    {hasActiveFilters && onClear ? (
                        <button
                            type="button"
                            onClick={onClear}
                            className="store-btn-ghost border border-white/20 text-store-surface hover:bg-white/10 hover:text-white"
                        >
                            مسح الفلاتر
                        </button>
                    ) : null}
                </div>

                <div className="relative mt-5">
                    <IconSearch className="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-store-primary/40" />
                    <input
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="ابحث عن طبق، وصف، أو قسم…"
                        autoComplete="off"
                        className="store-input border-0 py-3.5 pe-12 ps-12 shadow-md"
                        aria-label="بحث في القائمة"
                    />
                    {loading ? (
                        <span className="absolute end-12 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin rounded-full border-2 border-store-primary/20 border-t-store-secondary" />
                    ) : null}
                    {q ? (
                        <button
                            type="button"
                            onClick={() => setQ('')}
                            className="absolute end-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-store-primary/50 transition hover:bg-store-primary/5 hover:text-store-primary"
                            aria-label="مسح البحث"
                        >
                            <IconX className="h-4 w-4" />
                        </button>
                    ) : null}
                </div>
            </div>

            <div className="space-y-5 p-5 sm:p-6">
                {!hideCategoryFilter ? (
                <div>
                    <div className="mb-3 flex items-center gap-2 text-sm font-bold text-store-primary">
                        <IconFilter className="h-4 w-4 text-store-secondary" />
                        <span>القسم</span>
                    </div>
                    <div className="-mx-1 flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
                        <button
                            type="button"
                            onClick={() => setCategory('')}
                            className={category === '' ? 'store-chip store-chip-active' : 'store-chip'}
                        >
                            الكل
                            {total > 0 && category === '' ? (
                                <span className="me-1 opacity-70">({total})</span>
                            ) : null}
                        </button>
                        {categories.map((cat) => (
                            <button
                                key={cat.slug}
                                type="button"
                                onClick={() => setCategory(cat.slug)}
                                className={category === cat.slug ? 'store-chip store-chip-active' : 'store-chip'}
                            >
                                {cat.name}
                                {cat.published_products_count > 0 ? (
                                    <span className="me-1 text-xs opacity-70">({cat.published_products_count})</span>
                                ) : null}
                            </button>
                        ))}
                    </div>
                </div>
                ) : null}

                <div>
                    <p className="mb-3 text-sm font-bold text-store-primary">الترتيب</p>
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        {sortOptions.map((opt) => (
                            <button
                                key={opt.value}
                                type="button"
                                onClick={() => setSort(opt.value)}
                                aria-pressed={sort === opt.value}
                                className={`rounded-xl border px-3 py-3 text-start transition-all duration-fast ease-smooth ${
                                    sort === opt.value
                                        ? 'border-store-secondary bg-store-secondary/10 ring-1 ring-store-secondary/50'
                                        : 'border-store-border bg-store-surface hover:border-store-secondary/40 hover:bg-store-cream'
                                }`}
                            >
                                <span className="block text-sm font-bold text-store-primary">{opt.label}</span>
                                <span className="mt-0.5 block text-xs text-store-primary/50">{opt.hint}</span>
                            </button>
                        ))}
                    </div>
                </div>

                {hasActiveFilters ? (
                    <div className="flex flex-wrap items-center gap-2 rounded-xl bg-store-primary/[0.04] px-4 py-3 text-xs">
                        <span className="font-bold text-store-primary/70">مفعّل:</span>
                        {q ? (
                            <span className="rounded-full bg-store-surface px-3 py-1 font-medium text-store-primary shadow-sm">
                                بحث: «{q}»
                            </span>
                        ) : null}
                        {activeCategory ? (
                            <span className="rounded-full bg-store-surface px-3 py-1 font-medium text-store-primary shadow-sm">
                                قسم: {activeCategory.name}
                            </span>
                        ) : null}
                        {sort !== 'latest' ? (
                            <span className="rounded-full bg-store-surface px-3 py-1 font-medium text-store-primary shadow-sm">
                                ترتيب: {sortOptions.find((o) => o.value === sort)?.label}
                            </span>
                        ) : null}
                        {meta.from && meta.to ? (
                            <span className="ms-auto text-store-primary/55">
                                عرض {meta.from}–{meta.to} من {total}
                            </span>
                        ) : null}
                    </div>
                ) : null}
            </div>
        </section>
    );
}
