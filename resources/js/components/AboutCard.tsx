import { MarkdownContent } from '@/components/MarkdownContent';
import { RelativeTime } from '@/components/RelativeTime';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Link } from '@inertiajs/react';
import type { Community } from '@/types';
import { about } from '@/routes/communities';

export function AboutCard({ community }: { community: Community }) {
    return (
        <Card>
            <CardHeader>
                <h2 className="text-text text-sm font-bold">
                    About c/{community.name}
                </h2>
            </CardHeader>
            <CardBody className="space-y-3 text-sm">
                {community.description ? (
                    <p className="text-text">{community.description}</p>
                ) : (
                    <p className="text-muted italic">No description yet.</p>
                )}

                <dl className="border-border text-muted flex gap-6 border-t pt-3 text-xs">
                    <div>
                        <dt>Members</dt>
                        <dd className="text-text text-base font-bold">
                            {community.members_count}
                        </dd>
                    </div>
                    <div>
                        <dt>Posts</dt>
                        <dd className="text-text text-base font-bold">
                            {community.posts_count}
                        </dd>
                    </div>
                </dl>

                <p className="text-muted text-xs">
                    Created <RelativeTime value={community.created_at} />
                    {community.creator ? (
                        <>
                            {' '}
                            by{' '}
                            <Link
                                href={`/u/${community.creator.username}`}
                                className="hover:underline"
                            >
                                u/{community.creator.username}
                            </Link>
                        </>
                    ) : null}
                </p>

                {community.rules ? (
                    <div className="border-border border-t pt-3">
                        <h3 className="text-muted pb-1 text-xs font-bold tracking-wide uppercase">
                            Rules
                        </h3>
                        <MarkdownContent
                            content={community.rules}
                            className="text-xs"
                        />
                        <Link
                            href={about(community.name).url}
                            className="text-primary mt-2 inline-block text-xs font-semibold hover:underline"
                        >
                            Read the full rules →
                        </Link>
                    </div>
                ) : null}
            </CardBody>
        </Card>
    );
}
