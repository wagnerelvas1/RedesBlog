import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

export function FormField({
    id,
    label,
    error,
    hint,
    children,
    className,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <label htmlFor={id} className="text-text block text-sm font-medium">
                {label}
            </label>
            {children}
            {hint ? <p className="text-muted text-xs">{hint}</p> : null}
            {error ? (
                <p role="alert" className="text-sm text-red-500">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
