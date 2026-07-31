import { Link } from '@inertiajs/react';

/**
 * @param {{ items?: Array<{ name: string, href?: string }> }} props
 */
export default function StoreBreadcrumbs({ items = [] }) {
    if (!items.length) {
        return null;
    }

    return (
        <nav aria-label="مسار التصفح" className="mb-6 text-caption text-store-muted">
            <ol className="flex flex-wrap items-center gap-1.5">
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;

                    return (
                        <li key={`${item.name}-${index}`} className="flex items-center gap-1.5">
                            {index > 0 ? (
                                <span aria-hidden="true" className="text-store-border-strong">
                                    /
                                </span>
                            ) : null}
                            {item.href && !isLast ? (
                                <Link
                                    href={item.href}
                                    className="transition duration-fast hover:text-store-secondary"
                                >
                                    {item.name}
                                </Link>
                            ) : (
                                <span
                                    className={isLast ? 'font-medium text-store-primary' : undefined}
                                    aria-current={isLast ? 'page' : undefined}
                                >
                                    {item.name}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
