const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

const FORMATTER = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

export function formatRelative(iso: string): string {
    const seconds = (Date.parse(iso) - Date.now()) / 1000;
    const absolute = Math.abs(seconds);

    for (const [unit, size] of UNITS) {
        if (absolute >= size) {
            return FORMATTER.format(Math.round(seconds / size), unit);
        }
    }

    return FORMATTER.format(Math.round(seconds), 'second');
}

/**
 * Shows a compact relative timestamp while exposing the full date to
 * assistive technology and on hover.
 */
export function RelativeTime({
    value,
    className,
}: {
    value: string;
    className?: string;
}) {
    const date = new Date(value);

    return (
        <time
            dateTime={value}
            title={date.toLocaleString()}
            className={className}
        >
            {formatRelative(value)}
        </time>
    );
}
