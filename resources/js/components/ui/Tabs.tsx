import { cn } from '@/lib/utils';

export type TabItem<T extends string> = {
    value: T;
    label: string;
};

export type TabsProps<T extends string> = {
    items: readonly TabItem<T>[];
    value: T;
    onChange: (value: T) => void;
    className?: string;
};

export function Tabs<T extends string>({
    items,
    value,
    onChange,
    className,
}: TabsProps<T>) {
    return (
        <div
            role="tablist"
            className={cn('flex items-center gap-1 overflow-x-auto', className)}
        >
            {items.map((item) => (
                <button
                    key={item.value}
                    type="button"
                    role="tab"
                    aria-selected={item.value === value}
                    onClick={() => onChange(item.value)}
                    className={cn(
                        'cursor-pointer rounded-full px-3 py-1.5 text-sm font-semibold whitespace-nowrap transition',
                        item.value === value
                            ? 'bg-surface-2 text-text'
                            : 'text-muted hover:bg-surface-2 hover:text-text',
                    )}
                >
                    {item.label}
                </button>
            ))}
        </div>
    );
}
