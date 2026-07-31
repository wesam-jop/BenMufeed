import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center border-b-2 px-1 pt-1 text-body font-medium leading-5 transition duration-fast ease-smooth focus:outline-none ' +
                (active
                    ? 'border-store-secondary text-store-primary'
                    : 'border-transparent text-store-muted hover:border-store-border-strong hover:text-store-primary') +
                className
            }
        >
            {children}
        </Link>
    );
}
