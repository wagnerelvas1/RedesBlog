import { cn } from '@/lib/utils';
import { useEffect } from 'react';
import type { ReactNode } from 'react';

export type DrawerProps = {
    open: boolean;
    onClose: () => void;
    children: ReactNode;
    label?: string;
    className?: string;
};

export function Drawer({
    open,
    onClose,
    children,
    label = 'Navigation',
    className,
}: DrawerProps) {
    useEffect(() => {
        if (!open) {
            return;
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose();
            }
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
        };
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 lg:hidden" role="presentation">
            <div
                className="absolute inset-0 bg-black/60"
                onClick={onClose}
                aria-hidden="true"
            />
            <aside
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className={cn(
                    'relative z-10 h-full w-72 max-w-[85vw] overflow-y-auto border-r border-border bg-surface',
                    className,
                )}
            >
                {children}
            </aside>
        </div>
    );
}
