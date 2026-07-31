export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p
            {...props}
            className={`mt-1.5 text-caption font-medium text-semantic-error ${className}`}
            role="alert"
        >
            {message}
        </p>
    ) : null;
}
