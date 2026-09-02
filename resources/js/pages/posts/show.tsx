import { AboutCard } from '@/components/AboutCard';
import { CommentForm } from '@/components/CommentForm';
import { CommentSortTabs } from '@/components/CommentSortTabs';
import { CommentTree } from '@/components/CommentTree';
import { GuestGate } from '@/components/GuestGate';
import { PostCard } from '@/components/PostCard';
import { Card, CardBody } from '@/components/ui/Card';
import { AppLayout } from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';
import type { Comment, CommentSort, Community, Post } from '@/types';

type Props = {
    community: Community;
    post: Post;
    comments: Comment[];
    commentSort: CommentSort;
    canComment: boolean;
};

export default function PostShow({
    community,
    post,
    comments,
    commentSort,
    canComment,
}: Props) {
    return (
        <>
            <Head title={post.title} />

            <PostCard post={post} variant="full" />

            <Card className="mt-3">
                <CardBody className="space-y-3">
                    <GuestGate message="Log in to leave a comment.">
                        {canComment ? (
                            <CommentForm
                                community={community.name}
                                postId={post.id}
                            />
                        ) : (
                            <p className="text-muted text-sm">
                                Join c/{community.name} to join the
                                conversation.
                            </p>
                        )}
                    </GuestGate>
                </CardBody>
            </Card>

            <div className="mt-4 mb-2">
                <CommentSortTabs sort={commentSort} />
            </div>

            <CommentTree
                comments={comments}
                community={community.name}
                postId={post.id}
                canComment={canComment}
            />
        </>
    );
}

// Inertia v3 calls the layout callback with the page props and expects a
// layout definition back, so the right rail is built from `props`, not `page`.
PostShow.layout = (props: Props) => [
    AppLayout,
    { rightRail: <AboutCard community={props.community} /> },
];
