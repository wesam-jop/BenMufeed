import { previewPhone, splitCountryKey } from '@/lib/phoneCountries';

const fieldClass =
    'w-full rounded-lg border border-store-primary/15 bg-store-surface px-3 py-2 text-sm text-store-primary shadow-sm placeholder:text-store-primary/40';

/**
 * @param {{
 *   id?: string,
 *   countryKey: string,
 *   localNumber: string,
 *   onCountryChange: (key: string) => void,
 *   onLocalChange: (value: string) => void,
 *   countries?: Array<{ key: string, dial: string, label: string, flag?: string }>,
 *   error?: string,
 * }} props
 */
export default function PhoneField({
    id = 'customer_phone',
    countryKey,
    localNumber,
    onCountryChange,
    onLocalChange,
    countries = [],
    error,
}) {
    const { dial } = splitCountryKey(countryKey);
    const preview = previewPhone(dial, localNumber);

    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-store-primary">
                رقم الهاتف *
            </label>
            <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-5">
                <div className="sm:col-span-2">
                    <label htmlFor={`${id}_country`} className="sr-only">
                        الدولة / رمز النداء
                    </label>
                    <select
                        id={`${id}_country`}
                        value={countryKey}
                        onChange={(e) => onCountryChange(e.target.value)}
                        className={fieldClass}
                    >
                        {countries.map((country) => (
                            <option key={country.key} value={country.key}>
                                {country.label}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="sm:col-span-3">
                    <label htmlFor={id} className="sr-only">
                        رقم الجوال
                    </label>
                    <input
                        id={id}
                        type="tel"
                        inputMode="tel"
                        autoComplete="tel-national"
                        value={localNumber}
                        onChange={(e) => onLocalChange(e.target.value)}
                        placeholder="9xxxxxxxx"
                        className={fieldClass}
                    />
                </div>
            </div>
            <p className="mt-1.5 text-xs text-store-muted">{preview}</p>
            {error ? <p className="mt-1 text-xs text-red-600">{error}</p> : null}
        </div>
    );
}
