import { cn } from '@/lib/utils';
import type { ButtonHTMLAttributes } from 'react';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'outline';
type Size = 'sm' | 'md' | 'lg' | 'icon';

const VARIANTS: Record<Variant, string> = {
    primary:
        'bg-primary text-primary-contrast hover:brightness-110 disabled:bg-primary/60',
    secondary:
        'bg-surface-2 text-text hover:bg-border/60 border border-border',
    ghost: 'text-muted hover:bg-surface-2 hover:text-text',
    danger: 'bg-red-600 text-white hover:bg-red-700 disabled:bg-red-600/60',
    outline:
        'border border-primary text-primary hover:bg-primary hover:text-primary-contrast',
};

const SIZES: Record<Size, string> = {
    sm: 'h-7 px-3 text-xs',
    md: 'h-9 px-4 text-sm',
    lg: 'h-11 px-6 text-base',
    icon: 'h-9 w-9',
};

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: Variant;
    size?: Size;
};

export function Button({
    className,
    variant = 'primary',
    size = 'md',
    type = 'button',
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            className={cn(
                'inline-flex cursor-pointer items-center justify-center gap-2 rounded-full font-semibold transition disabled:cursor-not-allowed disabled:opacity-60',
                VARIANTS[variant],
                SIZES[size],
                className,
            )}
            {...props}
        />
    );
}
