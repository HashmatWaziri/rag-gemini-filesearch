import { cn } from '@/lib/utils';
import ReactSelect, {
    type ClassNamesConfig,
    type GroupBase,
    type MultiValue,
    type SingleValue,
} from 'react-select';

export type MetronicSelectOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

export type MetronicSelectVariant = 'default' | 'solid' | 'transparent';
export type MetronicSelectSize = 'sm' | 'md' | 'lg';

type MetronicSelectBaseProps = {
    options: MetronicSelectOption[];
    placeholder?: string;
    disabled?: boolean;
    isClearable?: boolean;
    isSearchable?: boolean;
    variant?: MetronicSelectVariant;
    size?: MetronicSelectSize;
    className?: string;
    id?: string;
    'aria-label'?: string;
    inputId?: string;
    hasError?: boolean;
    /** Remount when dependent options change (course → level → unit). */
    selectKey?: string;
};

export type MetronicSelectSingleProps = MetronicSelectBaseProps & {
    isMulti?: false;
    value: string | null | undefined;
    onChange: (value: string | null) => void;
};

export type MetronicSelectMultiProps = MetronicSelectBaseProps & {
    isMulti: true;
    value: string[];
    onChange: (value: string[]) => void;
};

export type MetronicSelectProps =
    | MetronicSelectSingleProps
    | MetronicSelectMultiProps;

function buildClassNames(
    variant: MetronicSelectVariant,
    size: MetronicSelectSize,
    hasError: boolean,
    isMulti: boolean,
): ClassNamesConfig<
    MetronicSelectOption,
    boolean,
    GroupBase<MetronicSelectOption>
> {
    const controlSize =
        size === 'sm'
            ? 'min-h-8 text-xs'
            : size === 'lg'
              ? 'min-h-10 text-base'
              : 'min-h-9 text-sm';

    const controlVariant =
        variant === 'solid'
            ? 'bg-muted/50 border-border'
            : variant === 'transparent'
              ? 'bg-transparent border-transparent shadow-none'
              : 'bg-background border-input';

    return {
        container: () => 'w-full',
        control: ({ isFocused, isDisabled }) =>
            cn(
                'flex w-full cursor-pointer items-center rounded-md border px-3 shadow-xs transition-[color,box-shadow]',
                controlSize,
                controlVariant,
                isFocused && 'border-ring ring-[3px] ring-ring/50',
                hasError && 'border-destructive ring-destructive/20',
                isDisabled && 'cursor-not-allowed opacity-50',
            ),
        valueContainer: () => cn('gap-1 p-0', isMulti && 'flex-wrap'),
        placeholder: () => 'text-muted-foreground',
        singleValue: () => 'text-foreground',
        multiValue: () =>
            'flex items-center gap-1 rounded-sm bg-primary/10 px-1.5 py-0.5 text-xs font-medium text-primary',
        multiValueLabel: () => 'text-primary',
        multiValueRemove: () =>
            'cursor-pointer rounded-sm p-0.5 text-primary/70 hover:bg-primary/20 hover:text-primary',
        input: () => 'text-foreground',
        indicatorsContainer: () => 'gap-1',
        clearIndicator: () =>
            'cursor-pointer rounded-sm p-1 text-muted-foreground hover:text-foreground',
        dropdownIndicator: () =>
            'cursor-pointer rounded-sm p-1 text-muted-foreground hover:text-foreground',
        indicatorSeparator: () => 'hidden',
        menuPortal: () => 'z-[200]',
        menu: () =>
            'mt-1 overflow-hidden rounded-md border border-border bg-popover text-popover-foreground shadow-md',
        menuList: () => 'max-h-60 overflow-y-auto p-1',
        option: ({ isFocused, isSelected, isDisabled }) =>
            cn(
                'cursor-pointer rounded-sm px-2 py-1.5 text-sm outline-none select-none',
                isFocused && 'bg-accent text-accent-foreground',
                isSelected && 'bg-primary/10 font-medium text-primary',
                isDisabled && 'pointer-events-none opacity-50',
            ),
        noOptionsMessage: () => 'px-2 py-1.5 text-sm text-muted-foreground',
    };
}

export function mapIdOptions(
    items: ReadonlyArray<{ id: number; name: string }>,
    placeholder?: MetronicSelectOption,
): MetronicSelectOption[] {
    const mapped = items.map((item) => ({
        value: String(item.id),
        label: item.name,
    }));

    return placeholder ? [placeholder, ...mapped] : mapped;
}

export function findMetronicOption(
    options: MetronicSelectOption[],
    value: string | null | undefined,
): MetronicSelectOption | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return options.find((option) => option.value === value) ?? null;
}

export function findMetronicOptions(
    options: MetronicSelectOption[],
    values: string[],
): MetronicSelectOption[] {
    const lookup = new Set(values);

    return options.filter((option) => lookup.has(option.value));
}

/**
 * Metronic 8 React-Select wrapper (preview.keenthemes.com/metronic8/react/docs/react-select).
 * Uses react-select with GLC semantic tokens, body portal, and fixed menu positioning.
 */
export function MetronicSelect(props: MetronicSelectProps) {
    const {
        options,
        placeholder = 'Select an option',
        disabled = false,
        isClearable = false,
        isSearchable = false,
        variant = 'solid',
        size = 'md',
        className,
        id,
        inputId,
        'aria-label': ariaLabel,
        hasError = false,
        selectKey,
        isMulti = false,
    } = props;

    const sharedProps = {
        unstyled: true as const,
        inputId: inputId ?? id,
        'aria-label': ariaLabel,
        instanceId: id,
        className: cn('react-select-styled', className),
        classNamePrefix: 'react-select',
        classNames: buildClassNames(variant, size, hasError, isMulti),
        options,
        placeholder,
        isDisabled: disabled,
        isClearable,
        isSearchable,
        isMulti,
        menuPortalTarget:
            typeof document !== 'undefined' ? document.body : undefined,
        menuPosition: 'fixed' as const,
        menuPlacement: 'auto' as const,
    };

    if (isMulti) {
        const { value, onChange } = props;
        const selected = findMetronicOptions(options, value);

        const handleChange = (
            next: MultiValue<MetronicSelectOption>,
        ) => {
            onChange(next.map((option) => option.value));
        };

        return (
            <ReactSelect
                key={selectKey}
                {...sharedProps}
                value={selected}
                onChange={handleChange}
            />
        );
    }

    const { value, onChange } = props;
    const selected = findMetronicOption(options, value ?? null);

    const handleChange = (option: SingleValue<MetronicSelectOption>) => {
        onChange(option?.value ?? null);
    };

    return (
        <ReactSelect
            key={selectKey}
            {...sharedProps}
            value={selected}
            onChange={handleChange}
        />
    );
}
