import { cn } from '@/lib/utils';

type Size = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

const SIZES: Record<Size, string> = {
    xs: 'h-5 w-5 text-[10px]',
    sm: 'h-6 w-6 text-[11px]',
    md: 'h-8 w-8 text-xs',
    lg: 'h-12 w-12 text-base',
    xl: 'h-20 w-20 text-2xl',
};

export type AvatarProps = {
    src?: string | null;
    name?: string | null;
    size?: Size;
    className?: string;
};

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export function Avatar({ src, name, size = 'md', className }: AvatarProps) {
    const label = name ?? '?';

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-surface-2 font-semibold text-muted select-none',
                SIZES[size],
                className,
            )}
        >
            {src ? (
                <img
                    src={src}
                    alt={label}
                    loading="lazy"
                    className="h-full w-full object-cover"
                />
            ) : (
                <span aria-hidden="true">{initials(label) || '?'}</span>
            )}
        </span>
    );
}
