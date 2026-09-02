import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

export type EmptyStateProps = {
    icon?: ReactNode;
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
};

export function EmptyState({
    icon,
    title,
    description,
    action,
    className,
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-2 rounded-lg border border-dashed border-border bg-surface px-6 py-12 text-center',
                className,
            )}
        >
            {icon ? (
                <div className="text-3xl text-muted" aria-hidden="true">
                    {icon}
                </div>
            ) : null}
            <p className="text-sm font-semibold text-text">{title}</p>
            {description ? (
                <p className="max-w-sm text-sm text-muted">{description}</p>
            ) : null}
            {action ? <div className="mt-2">{action}</div> : null}
        </div>
    );
}
