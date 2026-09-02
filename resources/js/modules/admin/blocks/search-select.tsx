import { Check, ChevronsUpDown } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

export type SearchSelectOption = {
    value: string;
    label: string;
    description?: string;
};

type Props = {
    options: SearchSelectOption[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    emptyMessage?: string;
    id?: string;
};

export default function SearchSelect({
    options,
    value,
    onChange,
    placeholder,
    emptyMessage,
    id,
}: Props) {
    const { t } = useI18n();
    const resolvedPlaceholder = placeholder ?? t('common.search_or_select');
    const resolvedEmpty = emptyMessage ?? t('common.no_results');
    const containerRef = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    const selected = options.find((item) => item.value === value);

    const filtered = useMemo(() => {
        const normalized = query.trim().toLowerCase();
        if (!normalized) {
            return options;
        }

        return options.filter(
            (item) =>
                item.label.toLowerCase().includes(normalized) ||
                item.description?.toLowerCase().includes(normalized),
        );
    }, [options, query]);

    useEffect(() => {
        if (!open && selected) {
            setQuery(selected.label);
        }
    }, [open, selected]);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpen(false);
                setQuery(selected?.label ?? '');
            }
        };

        document.addEventListener('mousedown', handleClickOutside);

        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [selected]);

    const handleFocus = () => {
        setOpen(true);
        if (selected) {
            setQuery(selected.label);
        }
    };

    const handleInputChange = (nextQuery: string) => {
        setQuery(nextQuery);
        setOpen(true);

        if (!nextQuery.trim()) {
            onChange('');
        }
    };

    const handleSelect = (option: SearchSelectOption) => {
        onChange(option.value);
        setQuery(option.label);
        setOpen(false);
    };

    return (
        <div ref={containerRef} className="relative w-full">
            <div className="relative">
                <Input
                    id={id}
                    value={query}
                    onChange={(e) => handleInputChange(e.target.value)}
                    onFocus={handleFocus}
                    placeholder={resolvedPlaceholder}
                    className="h-11 w-full pe-10 text-base"
                    autoComplete="off"
                />
                <ChevronsUpDown className="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            </div>

            {open && (
                <div className="absolute z-50 mt-1 max-h-64 w-full overflow-auto rounded-lg border bg-popover p-1 shadow-lg">
                    {filtered.length === 0 ? (
                        <div className="px-3 py-8 text-center text-sm text-muted-foreground">
                            {resolvedEmpty}
                        </div>
                    ) : (
                        filtered.map((item) => {
                            const isSelected = item.value === value;

                            return (
                                <button
                                    key={item.value}
                                    type="button"
                                    className={cn(
                                        'flex w-full items-center justify-between gap-3 rounded-md px-3 py-2.5 text-right text-sm transition-colors hover:bg-accent',
                                        isSelected && 'bg-accent',
                                    )}
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() => handleSelect(item)}
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate font-medium">{item.label}</span>
                                        {item.description && (
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {item.description}
                                            </span>
                                        )}
                                    </span>
                                    {isSelected && <Check className="size-4 shrink-0 text-primary" />}
                                </button>
                            );
                        })
                    )}
                </div>
            )}
        </div>
    );
}
