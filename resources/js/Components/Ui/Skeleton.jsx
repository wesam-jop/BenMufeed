export default function Skeleton({ className = '', ...props }) {
    return (
        <div
            className={`store-skeleton ${className}`}
            aria-hidden="true"
            {...props}
        />
    );
}
