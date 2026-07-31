import { Link } from '@inertiajs/react';

const LABEL_AR = {
    'pagination.previous': '« السابق',
    'pagination.next': 'التالي »',
    '&laquo; Previous': '« السابق',
    'Next &raquo;': 'التالي »',
    '&laquo; السابق': '« السابق',
    'التالي &raquo;': 'التالي »',
    Previous: 'السابق',
    Next: 'التالي',
};

function paginationLabel(label) {
    const trimmed = String(label).trim();

    return LABEL_AR[trimmed] ?? trimmed;
}

/**
 * ترقيم صفحات كامل من السيرفر (Inertia) — 15 عنصر لكل صفحة.
 *
 * @param {{ pagination: { links?: Array<{ url?: string|null, label: string, active: boolean }> } }} props
 */
export default function StorePagination({ pagination }) {
    const links = pagination?.links?.filter((l) => l.label) || [];

    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-10 flex flex-wrap justify-center gap-1.5" aria-label="ترقيم الصفحات">
            {links.map((link, idx) =>
                link.url ? (
                    <Link
                        key={idx}
                        href={link.url}
                        preserveScroll={false}
                        className={`rounded-lg px-3.5 py-2 text-body transition-all duration-fast ease-smooth ${
                            link.active
                                ? 'bg-store-primary font-semibold text-store-surface shadow-sm'
                                : 'border border-store-border bg-store-surface text-store-primary hover:border-store-secondary/50 hover:bg-store-cream'
                        }`}
                    >
                        {paginationLabel(link.label)}
                    </Link>
                ) : (
                    <span
                        key={idx}
                        className="rounded-lg px-3.5 py-2 text-body text-store-muted/40"
                    >
                        {paginationLabel(link.label)}
                    </span>
                ),
            )}
        </nav>
    );
}
