import { CommentForm } from '@/components/CommentForm';
import { MarkdownContent } from '@/components/MarkdownContent';
import { RelativeTime } from '@/components/RelativeTime';
import { VoteControl } from '@/components/VoteControl';
import { Avatar } from '@/components/ui/Avatar';
import { Lightbox } from '@/components/ui/Lightbox';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { Comment } from '@/types';
import { destroy as commentDestroy } from '@/routes/comments';
import { show as postShow } from '@/routes/posts';

const RENDER_DEPTH_CAP = 8;

export function CommentNode({
    comment,
    community,
    postId,
    canComment,
}: {
    comment: Comment;
    community: string;
    postId: number;
    canComment: boolean;
}) {
    const [collapsed, setCollapsed] = useState(false);
    const [replying, setReplying] = useState(false);
    const [editing, setEditing] = useState(false);
    const [lightbox, setLightbox] = useState(false);

    const atCap = comment.depth + 1 >= RENDER_DEPTH_CAP;

    return (
        <article className="relative">
            <div className="flex gap-2">
                <button
                    type="button"
                    onClick={() => setCollapsed((value) => !value)}
                    aria-expanded={!collapsed}
                    aria-label={collapsed ? 'Expand thread' : 'Collapse thread'}
                    className="group flex w-4 shrink-0 cursor-pointer flex-col items-center"
                >
                    <span className="text-muted text-[10px]">
                        {collapsed ? '+' : '−'}
                    </span>
                    {!collapsed ? (
                        <span className="bg-border group-hover:bg-primary mt-1 w-px flex-1 transition" />
                    ) : null}
                </button>

                <div className="min-w-0 flex-1 pb-3">
                    <div className="text-muted flex flex-wrap items-center gap-x-2 text-xs">
                        {comment.author ? (
                            <>
                                <Avatar
                                    src={comment.author.avatar_url}
                                    name={comment.author.name}
                                    size="xs"
                                />
                                <Link
                                    href={`/u/${comment.author.username}`}
                                    className="text-text font-semibold hover:underline"
                                >
                                    u/{comment.author.username}
                                </Link>
                            </>
                        ) : (
                            <span className="italic">[deleted]</span>
                        )}
                        <span aria-hidden="true">·</span>
                        <RelativeTime value={comment.created_at} />
                        {comment.edited_at ? (
                            <span className="italic">(edited)</span>
                        ) : null}
                        {collapsed && comment.replies_count > 0 ? (
                            <span>
                                · {comment.replies_count} repl
                                {comment.replies_count === 1 ? 'y' : 'ies'}
                            </span>
                        ) : null}
                    </div>

                    {collapsed ? null : (
                        <>
                            {comment.is_deleted ? (
                                <p className="text-muted mt-1 text-sm italic">
                                    [removed]
                                </p>
                            ) : editing ? (
                                <div className="mt-2">
                                    <CommentForm
                                        community={community}
                                        postId={postId}
                                        commentId={comment.id}
                                        initialBody={comment.body}
                                        submitLabel="Save"
                                        autoFocus
                                        onDone={() => setEditing(false)}
                                        onCancel={() => setEditing(false)}
                                    />
                                </div>
                            ) : (
                                <>
                                    <MarkdownContent
                                        content={comment.body}
                                        className="mt-1"
                                    />
                                    {comment.attachment ? (
                                        <button
                                            type="button"
                                            onClick={() => setLightbox(true)}
                                            className="mt-2 block cursor-zoom-in"
                                        >
                                            <img
                                                src={comment.attachment.url}
                                                alt={
                                                    comment.attachment
                                                        .original_name
                                                }
                                                loading="lazy"
                                                className="border-border max-h-72 rounded border object-cover"
                                            />
                                        </button>
                                    ) : null}
                                </>
                            )}

                            <div className="text-muted mt-1 flex flex-wrap items-center gap-1 text-xs font-semibold">
                                {!comment.is_deleted ? (
                                    <VoteControl
                                        votableType="comment"
                                        id={comment.id}
                                        score={comment.score}
                                        viewerVote={comment.viewer_vote}
                                        orientation="horizontal"
                                    />
                                ) : null}

                                {canComment && !comment.is_deleted ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setReplying((value) => !value)
                                        }
                                        className="hover:bg-surface-2 cursor-pointer rounded px-2 py-1 transition"
                                    >
                                        Reply
                                    </button>
                                ) : null}

                                {comment.can_update ? (
                                    <button
                                        type="button"
                                        onClick={() => setEditing(true)}
                                        className="hover:bg-surface-2 cursor-pointer rounded px-2 py-1 transition"
                                    >
                                        Edit
                                    </button>
                                ) : null}

                                {comment.can_delete ? (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (
                                                window.confirm(
                                                    'Delete this comment?',
                                                )
                                            ) {
                                                router.delete(
                                                    commentDestroy([
                                                        community,
                                                        postId,
                                                        comment.id,
                                                    ]).url,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                        className="hover:bg-surface-2 cursor-pointer rounded px-2 py-1 text-red-500 transition"
                                    >
                                        Delete
                                    </button>
                                ) : null}
                            </div>

                            {replying ? (
                                <div className="mt-2">
                                    <CommentForm
                                        community={community}
                                        postId={postId}
                                        parentId={comment.id}
                                        submitLabel="Reply"
                                        autoFocus
                                        onDone={() => setReplying(false)}
                                        onCancel={() => setReplying(false)}
                                    />
                                </div>
                            ) : null}

                            {comment.replies.length > 0 ? (
                                <div className="border-border mt-2 border-l pl-2">
                                    {comment.replies.map((reply) => (
                                        <CommentNode
                                            key={reply.id}
                                            comment={reply}
                                            community={community}
                                            postId={postId}
                                            canComment={canComment}
                                        />
                                    ))}
                                </div>
                            ) : null}

                            {atCap && comment.replies_count > 0 ? (
                                <Link
                                    href={
                                        postShow([community, postId], {
                                            query: { comment: comment.id },
                                        }).url
                                    }
                                    className="text-primary mt-1 inline-block text-xs font-semibold hover:underline"
                                >
                                    Continue this thread →
                                </Link>
                            ) : comment.has_more_replies ? (
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.reload({ only: ['comments'] })
                                    }
                                    className="text-primary mt-1 cursor-pointer text-xs font-semibold hover:underline"
                                >
                                    Load more replies
                                </button>
                            ) : null}
                        </>
                    )}
                </div>
            </div>

            {comment.attachment ? (
                <Lightbox
                    src={lightbox ? comment.attachment.url : null}
                    alt={comment.attachment.original_name}
                    onClose={() => setLightbox(false)}
                />
            ) : null}
        </article>
    );
}
