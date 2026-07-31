import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

function buildParams({ q, sort, category }) {
    return {
        q: q?.trim() || undefined,
        sort: sort && sort !== 'latest' ? sort : undefined,
        category: category || undefined,
    };
}

const defaultPartialKeys = ['products', 'pagination', 'filters', 'catalogMeta', 'seo', 'structuredData'];

/**
 * فلاتر فورية عبر Inertia (بدون إعادة تحميل كاملة) + ترقيم صفحات من السيرفر عبر روابط Inertia.
 *
 * @param {{ q?: string, sort?: string, category?: string }} initialFilters
 * @param {{ basePath?: string, only?: string[] }} [options]
 */
export function useInertiaProductCatalog(initialFilters, options = {}) {
    const basePath = options.basePath ?? '/products';
    const only = options.only ?? defaultPartialKeys;

    const [q, setQ] = useState(initialFilters.q || '');
    const [sort, setSort] = useState(initialFilters.sort || 'latest');
    const [category, setCategory] = useState(initialFilters.category || '');
    const [loading, setLoading] = useState(false);

    const skipNext = useRef(true);
    const debounceRef = useRef(null);

    useEffect(() => {
        setQ(initialFilters.q || '');
        setSort(initialFilters.sort || 'latest');
        setCategory(initialFilters.category || '');
    }, [initialFilters.q, initialFilters.sort, initialFilters.category]);

    const visitCatalog = useCallback(
        (params, { debounce = false } = {}) => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }

            const run = () => {
                router.get(basePath, buildParams(params), {
                    preserveState: true,
                    replace: true,
                    only,
                    onStart: () => setLoading(true),
                    onFinish: () => setLoading(false),
                });
            };

            if (debounce) {
                debounceRef.current = setTimeout(run, 300);
            } else {
                run();
            }
        },
        [basePath, only],
    );

    useEffect(() => {
        if (skipNext.current) {
            skipNext.current = false;
            return;
        }

        visitCatalog({ q, sort, category }, { debounce: true });

        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [q, sort, category, visitCatalog]);

    const setSortInstant = useCallback((value) => {
        setSort(value);
    }, []);

    const setCategoryInstant = useCallback((slug) => {
        setCategory(slug);
    }, []);

    const clearFilters = useCallback(() => {
        setQ('');
        setSort('latest');
        setCategory('');
    }, []);

    const hasActiveFilters = Boolean(q.trim() || category || sort !== 'latest');

    return {
        q,
        setQ,
        sort,
        setSort: setSortInstant,
        category,
        setCategory: setCategoryInstant,
        loading,
        clearFilters,
        hasActiveFilters,
    };
}
