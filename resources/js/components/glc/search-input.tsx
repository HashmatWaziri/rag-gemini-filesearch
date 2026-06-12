import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Search, X } from 'lucide-react';
import { type ComponentProps } from 'react';

interface GlcSearchInputProps extends Omit<
    ComponentProps<'input'>,
    'type' | 'className'
> {
    value: string;
    onValueChange: (value: string) => void;
    className?: string;
    inputClassName?: string;
}

export function GlcSearchInput({
    value,
    onValueChange,
    placeholder = 'Search…',
    className,
    inputClassName,
    ...props
}: GlcSearchInputProps) {
    return (
        <div className={cn('relative', className)}>
            <Search className="absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                type="search"
                value={value}
                onChange={(e) => onValueChange(e.target.value)}
                placeholder={placeholder}
                className={cn('w-40 ps-9', inputClassName)}
                {...props}
            />
            {value.length > 0 && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute end-1.5 top-1/2 h-6 w-6 -translate-y-1/2"
                    onClick={() => onValueChange('')}
                    aria-label="Clear search"
                >
                    <X className="size-3.5" />
                </Button>
            )}
        </div>
    );
}
