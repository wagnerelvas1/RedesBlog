import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'react';

type Tone = 'neutral' | 'primary' | 'success' | 'danger';

const TONES: Record<Tone, string> = {
    neutral: 'bg-surface-2 text-muted',
    primary: 'bg-primary/15 text-primary',
    success: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    danger: 'bg-red-500/15 text-red-600 dark:text-red-400',
};

export type BadgeProps = HTMLAttributes<HTMLSpanElement> & { tone?: Tone };

export function Badge({ className, tone = 'neutral', ...props }: BadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                TONES[tone],
                className,
            )}
            {...props}
        />
    );
}
