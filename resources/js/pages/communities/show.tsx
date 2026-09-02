import { AboutCard } from '@/components/AboutCard';
import { CommunityHeader } from '@/components/CommunityHeader';
import { PostList } from '@/components/PostList';
import { SortTabs } from '@/components/SortTabs';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import type {
    Community,
    CommunityPermissions,
    Membership,
    Post,
    PostSort,
    TopRange,
} from '@/types';
import { create as postCreate } from '@/routes/posts';

type Props = {
    community: Community;
    membership: Membership;
    permissions: CommunityPermissions;
    posts: Post[];
    pagination: { next_cursor: string | null };
    filters: { sort: PostSort; range: TopRange };
};

export default function CommunityShow({
    community,
    membership,
    permissions,
    posts,
    pagination,
    filters,
}: Props) {
    return (
        <>
            <Head title={`c/${community.name}`} />

            <CommunityHeader
                community={community}
                membership={membership}
                permissions={permissions}
            />

            <div className="xl:hidden">
                <div className="mb-4">
                    <AboutCard community={community} />
                </div>
            </div>

            <SortTabs
                sort={filters.sort}
                range={filters.range}
                scope={`community:${community.name}`}
            />

            <PostList
                posts={posts}
                nextCursor={pagination.next_cursor}
                emptyState={
                    <EmptyState
                        icon="📝"
                        title="No posts yet"
                        description={
                            permissions.can_post
                                ? 'Be the first to post in this community.'
                                : 'Join this community to start posting.'
                        }
                        action={
                            permissions.can_post ? (
                                <Link href={postCreate(community.name).url}>
                                    <Button size="sm">Create post</Button>
                                </Link>
                            ) : undefined
                        }
                    />
                }
            />
        </>
    );
}

// Inertia v3 calls the layout callback with the page props and expects a
// layout definition back, so the right rail is built from `props`, not `page`.
CommunityShow.layout = (props: Props) => [
    AppLayout,
    { rightRail: <AboutCard community={props.community} /> },
];
