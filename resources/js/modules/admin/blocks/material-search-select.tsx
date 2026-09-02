import type { SearchSelectOption } from '@/components/admin/search-select';
import SearchSelect from '@/components/admin/search-select';

export type MaterialSearchOption = {
    id: number;
    name: string;
    cost_price: number;
    unit: { name: string; abbreviation: string } | null;
};

type Props = {
    materials: MaterialSearchOption[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
};

function formatMaterialLabel(material: MaterialSearchOption): string {
    return `${material.name}${material.unit ? ` (${material.unit.abbreviation})` : ''}`;
}

export default function MaterialSearchSelect({
    materials,
    value,
    onChange,
    placeholder = 'ابحث عن مادة أو اختر من القائمة...',
}: Props) {
    const options: SearchSelectOption[] = materials.map((item) => ({
        value: String(item.id),
        label: formatMaterialLabel(item),
    }));

    return (
        <SearchSelect
            options={options}
            value={value}
            onChange={onChange}
            placeholder={placeholder}
            emptyMessage="لا توجد مادة بهذا الاسم"
        />
    );
}
