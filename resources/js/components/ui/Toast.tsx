import { cn } from '@/lib/utils';
import { useEffect } from 'react';

export type ToastProps = {
    message: string;
    tone?: 'success' | 'error';
    onDismiss: () => void;
    duration?: number;
};

export function Toast({
    message,
    tone = 'success',
    onDismiss,
    duration = 5000,
}: ToastProps) {
    useEffect(() => {
        const timer = window.setTimeout(onDismiss, duration);

        return () => window.clearTimeout(timer);
    }, [onDismiss, duration]);

    return (
        <div
            role="status"
            aria-live="polite"
            className={cn(
                'pointer-events-auto flex items-center gap-3 rounded-md border px-4 py-3 text-sm shadow-lg',
                tone === 'success'
                    ? 'border-emerald-500/40 bg-surface text-text'
                    : 'border-red-500/40 bg-surface text-text',
            )}
        >
            <span
                aria-hidden="true"
                className={cn(
                    'h-2 w-2 shrink-0 rounded-full',
                    tone === 'success' ? 'bg-emerald-500' : 'bg-red-500',
                )}
            />
            <span className="flex-1">{message}</span>
            <button
                type="button"
                onClick={onDismiss}
                aria-label="Dismiss"
                className="cursor-pointer text-muted transition hover:text-text"
            >
                ×
            </button>
        </div>
    );
}
