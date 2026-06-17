import { MetronicSelect } from '@/components/glc/metronic-select';
import { Label } from '@/components/ui/label';
import { type MaterialKindOption } from './types';
import { labelClass } from './ui';

interface MaterialKindSelectProps {
    value: string;
    onChange: (value: string) => void;
    options: MaterialKindOption[];
    error?: string;
    disabled?: boolean;
    placeholder?: string;
    label?: string;
}

export default function MaterialKindSelect({
    value,
    onChange,
    options,
    error,
    disabled = false,
    placeholder = 'Select material type',
    label = 'Material type',
}: MaterialKindSelectProps) {
    return (
        <div>
            <Label className={labelClass}>{label}</Label>
            <MetronicSelect
                value={value || null}
                onChange={(next) => onChange(next ?? '')}
                options={options.map((option) => ({
                    value: option.value,
                    label: option.label,
                }))}
                placeholder={placeholder}
                disabled={disabled}
                isSearchable={false}
            />
            {error && (
                <p className="mt-1 text-xs text-red-600">{error}</p>
            )}
        </div>
    );
}
