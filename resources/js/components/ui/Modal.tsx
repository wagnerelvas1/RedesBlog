import { cn } from '@/lib/utils';
import { useEffect, useRef } from 'react';
import type { ReactNode } from 'react';

export type ModalProps = {
    open: boolean;
    onClose: () => void;
    title?: string;
    children: ReactNode;
    footer?: ReactNode;
    className?: string;
};

const FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function Modal({
    open,
    onClose,
    title,
    children,
    footer,
    className,
}: ModalProps) {
    const panelRef = useRef<HTMLDivElement>(null);
    const returnFocusRef = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        returnFocusRef.current = document.activeElement as HTMLElement | null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const panel = panelRef.current;
        panel?.querySelector<HTMLElement>(FOCUSABLE)?.focus();

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose();

                return;
            }

            if (event.key !== 'Tab' || !panel) {
                return;
            }

            const targets = Array.from(
                panel.querySelectorAll<HTMLElement>(FOCUSABLE),
            );

            if (targets.length === 0) {
                return;
            }

            const first = targets[0];
            const last = targets[targets.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
            returnFocusRef.current?.focus();
        };
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="presentation"
        >
            <div
                className="absolute inset-0 bg-black/60"
                onClick={onClose}
                aria-hidden="true"
            />
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-label={title}
                className={cn(
                    'relative z-10 w-full max-w-md rounded-lg border border-border bg-surface shadow-xl',
                    className,
                )}
            >
                {title ? (
                    <div className="border-b border-border px-4 py-3">
                        <h2 className="text-base font-semibold text-text">
                            {title}
                        </h2>
                    </div>
                ) : null}
                <div className="px-4 py-4 text-sm text-text">{children}</div>
                {footer ? (
                    <div className="flex justify-end gap-2 border-t border-border px-4 py-3">
                        {footer}
                    </div>
                ) : null}
            </div>
        </div>
    );
}
