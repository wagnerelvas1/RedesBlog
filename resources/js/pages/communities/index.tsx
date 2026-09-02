import { CommunityCard } from '@/components/CommunityCard';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { useAuthUser } from '@/hooks/usePage';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { CommunitySummary, Paginated } from '@/types';
import {
    create as communityCreate,
    index as communityIndex,
} from '@/routes/communities';

type Props = {
    communities: Paginated<CommunitySummary & { is_member?: boolean }>;
    filters: { search: string | null; sort: string; filter: string };
};

export default function CommunitiesIndex({ communities, filters }: Props) {
    const user = useAuthUser();
    const [search, setSearch] = useState(filters.search ?? '');

    function apply(next: Record<string, string | undefined>) {
        router.get(
            communityIndex().url,
            {
                search: search || undefined,
                sort: filters.sort,
                filter: filters.filter,
                ...next,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Communities" />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <h1 className="text-text mr-auto text-xl font-bold">
                    Communities
                </h1>
                {user ? (
                    <Link href={communityCreate().url}>
                        <Button size="sm">Create community</Button>
                    </Link>
                ) : null}
            </div>

            <form
                className="mb-4 flex flex-wrap gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    apply({});
                }}
            >
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search by name or title"
                    aria-label="Search communities"
                    className="min-w-48 flex-1"
                />
                <Select
                    value={filters.sort}
                    aria-label="Sort communities"
                    onChange={(event) => apply({ sort: event.target.value })}
                >
                    <option value="members">Most members</option>
                    <option value="new">Newest</option>
                    <option value="name">Name</option>
                </Select>
                {user ? (
                    <Select
                        value={filters.filter}
                        aria-label="Filter communities"
                        onChange={(event) =>
                            apply({ filter: event.target.value })
                        }
                    >
                        <option value="all">All</option>
                        <option value="joined">Joined</option>
                    </Select>
                ) : null}
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </form>

            {communities.data.length === 0 ? (
                <EmptyState
                    icon="🔍"
                    title="No communities found"
                    description="Try a different search, or create the first one."
                />
            ) : (
                <div className="flex flex-col gap-2">
                    {communities.data.map((community) => (
                        <CommunityCard
                            key={community.id}
                            community={community}
                        />
                    ))}
                </div>
            )}

            {communities.last_page > 1 ? (
                <nav
                    aria-label="Pagination"
                    className="mt-4 flex items-center justify-center gap-2 text-sm"
                >
                    <Button
                        variant="secondary"
                        size="sm"
                        disabled={communities.current_page <= 1}
                        onClick={() =>
                            apply({
                                page: String(communities.current_page - 1),
                            })
                        }
                    >
                        Previous
                    </Button>
                    <span className="text-muted">
                        Page {communities.current_page} of{' '}
                        {communities.last_page}
                    </span>
                    <Button
                        variant="secondary"
                        size="sm"
                        disabled={
                            communities.current_page >= communities.last_page
                        }
                        onClick={() =>
                            apply({
                                page: String(communities.current_page + 1),
                            })
                        }
                    >
                        Next
                    </Button>
                </nav>
            ) : null}
        </>
    );
}

CommunitiesIndex.layout = [AppLayout];
