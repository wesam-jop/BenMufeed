export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={`store-btn-primary ${className}`}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
