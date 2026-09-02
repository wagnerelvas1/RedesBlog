import { cn } from '@/lib/utils';
import type { SelectHTMLAttributes } from 'react';

export type SelectProps = SelectHTMLAttributes<HTMLSelectElement>;

export function Select({ className, ...props }: SelectProps) {
    return (
        <select
            className={cn(
                'cursor-pointer rounded-md border border-border bg-surface px-3 py-2 text-sm text-text transition focus:border-primary',
                className,
            )}
            {...props}
        />
    );
}
