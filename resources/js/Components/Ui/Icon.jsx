import { heroiconPaths } from '@/lib/heroiconPaths';

export default function Icon({ name, className = 'ui-icon', ...props }) {
    const paths = heroiconPaths[name];
    if (!paths) {
        return null;
    }

    const list = Array.isArray(paths) ? paths : [paths];

    return (
        <svg
            className={className}
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth={1.5}
            stroke="currentColor"
            aria-hidden="true"
            {...props}
        >
            {list.map((d) => (
                <path key={d} strokeLinecap="round" strokeLinejoin="round" d={d} />
            ))}
        </svg>
    );
}
