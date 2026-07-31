export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={`store-label ${className}`}
        >
            {value ? value : children}
        </label>
    );
}
