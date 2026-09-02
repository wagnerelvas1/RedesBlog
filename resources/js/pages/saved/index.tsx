import { PostList } from '@/components/PostList';
import { EmptyState } from '@/components/ui/EmptyState';
import { AppLayout } from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';
import type { Post } from '@/types';

type Props = {
    posts: Post[];
    pagination: { next_cursor: string | null };
};

export default function SavedPosts({ posts, pagination }: Props) {
    return (
        <>
            <Head title="Saved posts" />

            <h1 className="text-text mb-3 text-xl font-bold">Saved posts</h1>

            <PostList
                posts={posts}
                nextCursor={pagination.next_cursor}
                emptyState={
                    <EmptyState
                        icon="🔖"
                        title="Nothing saved yet"
                        description="Use the Save action on any post to keep it here."
                    />
                }
            />
        </>
    );
}

SavedPosts.layout = [AppLayout];
