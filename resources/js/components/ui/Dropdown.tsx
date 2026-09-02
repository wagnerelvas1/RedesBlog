import { cn } from '@/lib/utils';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

export type DropdownProps = {
    trigger: (props: { open: boolean; toggle: () => void }) => ReactNode;
    children: (props: { close: () => void }) => ReactNode;
    align?: 'left' | 'right';
    className?: string;
};

export function Dropdown({
    trigger,
    children,
    align = 'right',
    className,
}: DropdownProps) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        function onPointerDown(event: MouseEvent) {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    return (
        <div ref={containerRef} className="relative">
            {trigger({ open, toggle: () => setOpen((value) => !value) })}
            {open ? (
                <div
                    role="menu"
                    className={cn(
                        'absolute z-40 mt-2 min-w-48 overflow-hidden rounded-md border border-border bg-surface py-1 shadow-lg',
                        align === 'right' ? 'right-0' : 'left-0',
                        className,
                    )}
                >
                    {children({ close: () => setOpen(false) })}
                </div>
            ) : null}
        </div>
    );
}

export function DropdownItem({
    className,
    ...props
}: React.ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            type="button"
            role="menuitem"
            className={cn(
                'block w-full cursor-pointer px-3 py-2 text-left text-sm text-text transition hover:bg-surface-2',
                className,
            )}
            {...props}
        />
    );
}
