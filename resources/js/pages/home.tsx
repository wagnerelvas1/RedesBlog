import { PostList } from '@/components/PostList';
import { SortTabs } from '@/components/SortTabs';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { useAuthUser } from '@/hooks/usePage';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import type { Post, PostSort, TopRange } from '@/types';
import { index as communityIndex } from '@/routes/communities';

type Props = {
    posts: Post[];
    pagination: { next_cursor: string | null };
    filters: { sort: PostSort; range: TopRange };
};

export default function Home({ posts, pagination, filters }: Props) {
    const user = useAuthUser();

    return (
        <>
            <Head title="Home" />

            <SortTabs sort={filters.sort} range={filters.range} scope="home" />

            <PostList
                posts={posts}
                nextCursor={pagination.next_cursor}
                emptyState={
                    <EmptyState
                        icon="🌱"
                        title="Your feed is empty"
                        description={
                            user
                                ? 'Join a few communities and their posts will show up here.'
                                : 'Explore the communities to find something to read.'
                        }
                        action={
                            <Link href={communityIndex().url}>
                                <Button size="sm">Explore communities</Button>
                            </Link>
                        }
                    />
                }
            />
        </>
    );
}

Home.layout = [AppLayout];
