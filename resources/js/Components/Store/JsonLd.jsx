import { Head } from '@inertiajs/react';

/**
 * بيانات منظمة Schema.org — تُعرض في HTML الأولي من السيرفر؛ هنا للتنقل داخل Inertia.
 *
 * @param {{ structuredData?: Array<Record<string, unknown>> }} props
 */
export default function JsonLd({ structuredData }) {
    if (!structuredData?.length) {
        return null;
    }

    return (
        <Head>
            {structuredData.map((block, index) => (
                <script
                    key={`ld-${index}`}
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(block) }}
                />
            ))}
        </Head>
    );
}
