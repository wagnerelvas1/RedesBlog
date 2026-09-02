import { useAppearance } from '@/hooks/useAppearance';
import { cn } from '@/lib/utils';
import type { Appearance } from '@/types';

const OPTIONS: { value: Appearance; label: string; icon: string }[] = [
    { value: 'light', label: 'Light theme', icon: '☀' },
    { value: 'dark', label: 'Dark theme', icon: '☾' },
    { value: 'system', label: 'Follow system theme', icon: '🖥' },
];

export function ThemeToggle({ className }: { className?: string }) {
    const { appearance, setAppearance } = useAppearance();

    return (
        <div
            role="group"
            aria-label="Theme"
            className={cn(
                'border-border bg-surface inline-flex items-center gap-0.5 rounded-full border p-0.5',
                className,
            )}
        >
            {OPTIONS.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    aria-label={option.label}
                    aria-pressed={appearance === option.value}
                    onClick={() => setAppearance(option.value)}
                    className={cn(
                        'cursor-pointer rounded-full px-2 py-1 text-xs transition',
                        appearance === option.value
                            ? 'bg-surface-2 text-text'
                            : 'text-muted hover:text-text',
                    )}
                >
                    <span aria-hidden="true">{option.icon}</span>
                </button>
            ))}
        </div>
    );
}
