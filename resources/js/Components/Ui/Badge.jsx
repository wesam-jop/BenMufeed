const variants = {
    success: 'store-badge-success',
    error: 'store-badge-error',
    warning: 'store-badge-warning',
    neutral: 'store-badge-neutral',
};

export default function Badge({ variant = 'neutral', className = '', children, ...props }) {
    return (
        <span className={`${variants[variant] || variants.neutral} ${className}`} {...props}>
            {children}
        </span>
    );
}
