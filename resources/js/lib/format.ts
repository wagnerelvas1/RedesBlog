/** Compact score display: 1200 -> "1.2k". */
export function compactNumber(value: number): string {
    const abs = Math.abs(value);

    if (abs < 1000) {
        return String(value);
    }

    if (abs < 1_000_000) {
        return `${(value / 1000).toFixed(abs < 10_000 ? 1 : 0)}k`;
    }

    return `${(value / 1_000_000).toFixed(1)}m`;
}
