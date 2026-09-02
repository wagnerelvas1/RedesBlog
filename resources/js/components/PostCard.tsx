import { ImageGallery } from '@/components/ImageGallery';
import { MarkdownContent } from '@/components/MarkdownContent';
import { RelativeTime } from '@/components/RelativeTime';
import { VoteControl } from '@/components/VoteControl';
import { Avatar } from '@/components/ui/Avatar';
import { Badge } from '@/components/ui/Badge';
import { Dropdown, DropdownItem } from '@/components/ui/Dropdown';
import { useAuthUser } from '@/hooks/usePage';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { Post } from '@/types';
import {
    destroy as postDestroy,
    edit as postEdit,
    pin as postPin,
    save as postSave,
    show as postShow,
    unpin as postUnpin,
    unsave as postUnsave,
} from '@/routes/posts';
import { login } from '@/routes';

export type PostCardProps = {
    post: Post;
    variant?: 'compact' | 'full';
};

export function PostCard({ post, variant = 'compact' }: PostCardProps) {
    const user = useAuthUser();
    const [saved, setSaved] = useState(post.is_saved);
    const full = variant === 'full';
    const href = postShow([post.community.name, post.id]).url;

    function toggleSave() {
        if (!user) {
            router.visit(login().url);

            return;
        }

        const next = !saved;
        setSaved(next);

        const endpoint = next ? postSave(post.id) : postUnsave(post.id);

        router.visit(endpoint.url, {
            method: endpoint.method,
            preserveScroll: true,
            preserveState: true,
            onError: () => setSaved(!next),
        });
    }

    function togglePin() {
        const endpoint = post.is_pinned
            ? postUnpin([post.community.name, post.id])
            : postPin([post.community.name, post.id]);

        router.visit(endpoint.url, {
            method: endpoint.method,
            preserveScroll: true,
        });
    }

    return (
        <article
            className={cn(
                'border-border bg-surface flex gap-2 rounded-lg border p-2 transition',
                !full && 'hover:border-muted',
            )}
        >
            <div className="hidden shrink-0 pt-1 sm:block">
                <VoteControl
                    votableType="post"
                    id={post.id}
                    score={post.score}
                    viewerVote={post.viewer_vote}
                />
            </div>

            <div className="min-w-0 flex-1">
                <div className="text-muted flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                    {post.is_pinned ? (
                        <Badge tone="primary">📌 Pinned</Badge>
                    ) : null}
                    <Link
                        href={`/c/${post.community.name}`}
                        className="text-text flex items-center gap-1 font-semibold hover:underline"
                    >
                        <Avatar
                            src={post.community.avatar_url}
                            name={post.community.title}
                            size="xs"
                        />
                        c/{post.community.name}
                    </Link>
                    <span aria-hidden="true">·</span>
                    <span>
                        by{' '}
                        {post.author ? (
                            <Link
                                href={`/u/${post.author.username}`}
                                className="hover:underline"
                            >
                                u/{post.author.username}
                            </Link>
                        ) : (
                            <span className="italic">[deleted]</span>
                        )}
                    </span>
                    <span aria-hidden="true">·</span>
                    <RelativeTime value={post.created_at} />
                    {post.edited_at ? (
                        <span className="italic">(edited)</span>
                    ) : null}
                </div>

                {full ? (
                    <h1 className="text-text mt-1 text-xl font-bold break-words">
                        {post.title}
                    </h1>
                ) : (
                    <h2 className="text-text mt-1 text-base font-semibold break-words">
                        <Link href={href} className="hover:underline">
                            {post.title}
                        </Link>
                    </h2>
                )}

                {full && post.body ? (
                    <MarkdownContent content={post.body} className="mt-2" />
                ) : null}

                {post.attachments.length > 0 ? (
                    full ? (
                        <ImageGallery
                            attachments={post.attachments}
                            className="mt-3"
                        />
                    ) : (
                        <Link href={href} className="mt-2 block">
                            <img
                                src={post.attachments[0].url}
                                alt={post.attachments[0].original_name}
                                loading="lazy"
                                className="border-border max-h-96 w-full rounded-lg border object-cover"
                            />
                        </Link>
                    )
                ) : null}

                <div className="text-muted mt-2 flex flex-wrap items-center gap-1 text-xs font-semibold">
                    <div className="sm:hidden">
                        <VoteControl
                            votableType="post"
                            id={post.id}
                            score={post.score}
                            viewerVote={post.viewer_vote}
                            orientation="horizontal"
                        />
                    </div>

                    <Link
                        href={href}
                        className="hover:bg-surface-2 rounded px-2 py-1 transition"
                    >
                        💬 {post.comments_count} comments
                    </Link>

                    <button
                        type="button"
                        onClick={toggleSave}
                        aria-pressed={saved}
                        className="hover:bg-surface-2 cursor-pointer rounded px-2 py-1 transition"
                    >
                        {saved ? '🔖 Saved' : '🔖 Save'}
                    </button>

                    {post.can_pin ? (
                        <button
                            type="button"
                            onClick={togglePin}
                            className="hover:bg-surface-2 cursor-pointer rounded px-2 py-1 transition"
                        >
                            {post.is_pinned ? '📌 Unpin' : '📌 Pin'}
                        </button>
                    ) : null}

                    {post.can_update || post.can_delete ? (
                        <Dropdown
                            align="left"
                            trigger={({ toggle }) => (
                                <button
                                    type="button"
                                    onClick={toggle}
                                    aria-label="Post actions"
                                    className="hover:bg-surface-2 cursor-pointer rounded px-2 py-1 transition"
                                >
                                    ⋯
                                </button>
                            )}
                        >
                            {({ close }) => (
                                <>
                                    {post.can_update ? (
                                        <Link
                                            href={
                                                postEdit([
                                                    post.community.name,
                                                    post.id,
                                                ]).url
                                            }
                                            onClick={close}
                                            className="text-text hover:bg-surface-2 block px-3 py-2 text-sm transition"
                                        >
                                            Edit
                                        </Link>
                                    ) : null}
                                    {post.can_delete ? (
                                        <DropdownItem
                                            className="text-red-500"
                                            onClick={() => {
                                                close();
                                                if (
                                                    window.confirm(
                                                        'Delete this post?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        postDestroy([
                                                            post.community.name,
                                                            post.id,
                                                        ]).url,
                                                    );
                                                }
                                            }}
                                        >
                                            Delete
                                        </DropdownItem>
                                    ) : null}
                                </>
                            )}
                        </Dropdown>
                    ) : null}
                </div>
            </div>
        </article>
    );
}
