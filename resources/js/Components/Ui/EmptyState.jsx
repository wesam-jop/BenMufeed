import { Link } from '@inertiajs/react';

export default function EmptyState({
    icon,
    title,
    description,
    actionLabel,
    actionHref,
    onAction,
    className = '',
}) {
    return (
        <div className={`store-empty-state ${className}`}>
            {icon ? <div className="store-empty-state-icon">{icon}</div> : null}
            {title ? <h3 className="store-empty-state-title">{title}</h3> : null}
            {description ? <p className="store-empty-state-desc">{description}</p> : null}
            {actionLabel && actionHref ? (
                <Link href={actionHref} className="store-btn-primary mt-6">
                    {actionLabel}
                </Link>
            ) : null}
            {actionLabel && onAction ? (
                <button type="button" onClick={onAction} className="store-btn-primary mt-6">
                    {actionLabel}
                </button>
            ) : null}
        </div>
    );
}
