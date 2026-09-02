import { Select } from '@/components/ui/Select';
import { Tabs } from '@/components/ui/Tabs';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import type { PostSort, TopRange } from '@/types';

const SORTS = [
    { value: 'hot' as const, label: '🔥 Hot' },
    { value: 'new' as const, label: '✨ New' },
    { value: 'top' as const, label: '🏆 Top' },
    { value: 'controversial' as const, label: '⚡ Controversial' },
];

const RANGES: { value: TopRange; label: string }[] = [
    { value: 'day', label: 'Today' },
    { value: 'week', label: 'This week' },
    { value: 'month', label: 'This month' },
    { value: 'year', label: 'This year' },
    { value: 'all', label: 'All time' },
];

/**
 * Feed sort selector. The choice is remembered per scope in localStorage so a
 * returning visitor lands on the ordering they last used.
 */
export function SortTabs({
    sort,
    range,
    scope,
}: {
    sort: PostSort;
    range: TopRange;
    scope: string;
}) {
    useEffect(() => {
        try {
            window.localStorage.setItem(`sort:${scope}`, sort);
        } catch {
            // Storage can be unavailable (private mode); the sort still works.
        }
    }, [sort, scope]);

    function apply(next: Partial<{ sort: PostSort; range: TopRange }>) {
        router.get(
            window.location.pathname,
            {
                sort: next.sort ?? sort,
                range:
                    (next.sort ?? sort) === 'top'
                        ? (next.range ?? range)
                        : undefined,
            },
            { preserveState: false, preserveScroll: true },
        );
    }

    return (
        <div className="border-border bg-surface mb-3 flex items-center gap-2 rounded-lg border px-2 py-1.5">
            <Tabs
                items={SORTS}
                value={sort}
                onChange={(value) => apply({ sort: value })}
            />
            {sort === 'top' ? (
                <Select
                    value={range}
                    aria-label="Time range"
                    className="ml-auto h-7 py-0 text-xs"
                    onChange={(event) =>
                        apply({ range: event.target.value as TopRange })
                    }
                >
                    {RANGES.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </Select>
            ) : null}
        </div>
    );
}
