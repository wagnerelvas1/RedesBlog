import { cn } from '@/lib/utils';
import type { ComponentPropsWithRef } from 'react';

export type TextareaProps = ComponentPropsWithRef<'textarea'> & {
    invalid?: boolean;
};

export function Textarea({ className, invalid, ...props }: TextareaProps) {
    return (
        <textarea
            aria-invalid={invalid || undefined}
            className={cn(
                'w-full rounded-md border bg-surface px-3 py-2 text-sm text-text transition placeholder:text-muted focus:border-primary disabled:opacity-60',
                invalid ? 'border-red-500' : 'border-border',
                className,
            )}
            {...props}
        />
    );
}
