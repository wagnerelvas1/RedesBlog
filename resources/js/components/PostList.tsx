import { PostCard } from '@/components/PostCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { Skeleton } from '@/components/ui/Skeleton';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import type { Post } from '@/types';

export type PostListProps = {
    posts: Post[];
    nextCursor: string | null;
    emptyState?: ReactNode;
};

/**
 * Infinite-scrolling feed. Loading the next page merges into the `posts` prop
 * server-side, so the list grows instead of being replaced.
 */
export function PostList({ posts, nextCursor, emptyState }: PostListProps) {
    const sentinelRef = useRef<HTMLDivElement>(null);
    const [loading, setLoading] = useState(false);

    const loadMore = useCallback(() => {
        if (!nextCursor || loading) {
            return;
        }

        setLoading(true);

        router.reload({
            data: { cursor: nextCursor },
            only: ['posts', 'pagination'],
            onFinish: () => setLoading(false),
        });
    }, [nextCursor, loading]);

    useEffect(() => {
        const sentinel = sentinelRef.current;

        if (!sentinel || !nextCursor) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    loadMore();
                }
            },
            { rootMargin: '400px' },
        );

        observer.observe(sentinel);

        return () => observer.disconnect();
    }, [loadMore, nextCursor]);

    if (posts.length === 0) {
        return (
            <>
                {emptyState ?? (
                    <EmptyState
                        icon="📭"
                        title="Nothing here yet"
                        description="Posts will show up here once they are published."
                    />
                )}
            </>
        );
    }

    return (
        <div className="flex flex-col gap-2">
            {posts.map((post) => (
                <PostCard key={post.id} post={post} />
            ))}

            {nextCursor ? (
                <div ref={sentinelRef} aria-hidden="true">
                    <PostSkeleton />
                </div>
            ) : null}

            {loading ? (
                <p
                    role="status"
                    className="text-muted py-2 text-center text-sm"
                >
                    Loading more posts…
                </p>
            ) : null}
        </div>
    );
}

export function PostSkeleton() {
    return (
        <div className="border-border bg-surface flex gap-2 rounded-lg border p-2">
            <Skeleton className="h-16 w-8" />
            <div className="flex-1 space-y-2 py-1">
                <Skeleton className="h-3 w-40" />
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-3 w-1/2" />
            </div>
        </div>
    );
}
