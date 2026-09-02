import { CommentNode } from '@/components/CommentNode';
import { EmptyState } from '@/components/ui/EmptyState';
import type { Comment } from '@/types';

export function CommentTree({
    comments,
    community,
    postId,
    canComment,
}: {
    comments: Comment[];
    community: string;
    postId: number;
    canComment: boolean;
}) {
    if (comments.length === 0) {
        return (
            <EmptyState
                icon="💬"
                title="No comments yet"
                description="Be the first to share what you think."
            />
        );
    }

    return (
        <div className="flex flex-col">
            {comments.map((comment) => (
                <CommentNode
                    key={comment.id}
                    comment={comment}
                    community={community}
                    postId={postId}
                    canComment={canComment}
                />
            ))}
        </div>
    );
}
