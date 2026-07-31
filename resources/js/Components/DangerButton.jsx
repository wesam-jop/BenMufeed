export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={`store-btn-destructive ${disabled ? 'opacity-40' : ''} ${className}`}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
