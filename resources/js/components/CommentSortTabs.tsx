import { Tabs } from '@/components/ui/Tabs';
import { router } from '@inertiajs/react';
import type { CommentSort } from '@/types';

const SORTS = [
    { value: 'best' as const, label: 'Best' },
    { value: 'new' as const, label: 'New' },
    { value: 'top' as const, label: 'Top' },
    { value: 'old' as const, label: 'Old' },
    { value: 'controversial' as const, label: 'Controversial' },
];

export function CommentSortTabs({ sort }: { sort: CommentSort }) {
    return (
        <Tabs
            items={SORTS}
            value={sort}
            onChange={(value) =>
                router.get(
                    window.location.pathname,
                    { sort: value },
                    { preserveScroll: true, preserveState: false },
                )
            }
        />
    );
}
