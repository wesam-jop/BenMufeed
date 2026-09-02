import { useMemo } from 'react';
import PhoneInput, { type Country, type Value } from 'react-phone-number-input';
import ar from 'react-phone-number-input/locale/ar';
import en from 'react-phone-number-input/locale/en';
import 'react-phone-number-input/style.css';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

type Props = {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    defaultCountry?: Country;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    autoFocus?: boolean;
};

export default function WhatsAppPhoneInput({
    id,
    value,
    onChange,
    defaultCountry = 'SY',
    disabled = false,
    className,
    placeholder,
    autoFocus = false,
}: Props) {
    const { locale, dir } = useI18n();
    const labels = useMemo(() => (locale === 'ar' ? ar : en), [locale]);

    return (
        <div dir="ltr" className={cn('whatsapp-phone-input', className)}>
            <PhoneInput
                id={id}
                international
                defaultCountry={defaultCountry}
                countries={undefined}
                labels={labels}
                value={(value || undefined) as Value | undefined}
                onChange={(next) => onChange(next ?? '')}
                disabled={disabled}
                placeholder={placeholder}
                autoFocus={autoFocus}
                numberInputProps={{
                    className:
                        'flex-1 border-0 bg-transparent px-2 py-2 text-sm outline-none placeholder:text-muted-foreground',
                    dir: 'ltr',
                }}
                className={cn(
                    'PhoneInput flex h-10 w-full rounded-md border border-input bg-background px-2 text-sm ring-offset-background',
                    'focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                    disabled && 'cursor-not-allowed opacity-50',
                )}
                style={{ direction: 'ltr' }}
            />
            {/* keep page RTL for surrounding form; input itself is LTR */}
            <span className="sr-only" lang={locale} dir={dir} />
        </div>
    );
}
