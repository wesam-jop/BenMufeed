import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                `block w-full border-s-4 py-2.5 pe-4 ps-3 text-start text-body font-medium transition duration-fast ease-smooth focus:outline-none ${
                    active
                        ? 'border-store-secondary bg-store-cream text-store-primary'
                        : 'border-transparent text-store-muted hover:border-store-border hover:bg-neutral-50 hover:text-store-primary'
                } ${className}`
            }
        >
            {children}
        </Link>
    );
}
