import { cn } from '@/lib/utils';
import type { InputHTMLAttributes } from 'react';

export type CheckboxProps = InputHTMLAttributes<HTMLInputElement> & {
    label?: string;
};

export function Checkbox({ className, label, id, ...props }: CheckboxProps) {
    const input = (
        <input
            id={id}
            type="checkbox"
            className={cn(
                'h-4 w-4 cursor-pointer rounded border-border accent-primary',
                className,
            )}
            {...props}
        />
    );

    if (!label) {
        return input;
    }

    return (
        <label
            htmlFor={id}
            className="inline-flex cursor-pointer items-center gap-2 text-sm text-text"
        >
            {input}
            {label}
        </label>
    );
}
