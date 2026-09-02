import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

export type TooltipProps = {
    label: string;
    children: ReactNode;
    className?: string;
};

export function Tooltip({ label, children, className }: TooltipProps) {
    return (
        <span className={cn('group relative inline-flex', className)}>
            {children}
            <span
                role="tooltip"
                className="pointer-events-none absolute bottom-full left-1/2 z-40 mb-1 -translate-x-1/2 rounded bg-surface-2 px-2 py-1 text-xs whitespace-nowrap text-text opacity-0 shadow transition group-hover:opacity-100 group-focus-within:opacity-100"
            >
                {label}
            </span>
        </span>
    );
}
