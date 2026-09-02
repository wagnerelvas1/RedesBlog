import { JoinButton } from '@/components/JoinButton';
import { Avatar } from '@/components/ui/Avatar';
import { Card, CardBody } from '@/components/ui/Card';
import { Link } from '@inertiajs/react';
import type { CommunitySummary } from '@/types';

export function CommunityCard({
    community,
}: {
    community: CommunitySummary & { is_member?: boolean };
}) {
    return (
        <Card>
            <CardBody className="flex items-center gap-3">
                <Avatar
                    src={community.avatar_url}
                    name={community.title}
                    size="lg"
                />
                <div className="min-w-0 flex-1">
                    <Link
                        href={`/c/${community.name}`}
                        className="text-text block truncate font-semibold hover:underline"
                    >
                        {community.title}
                    </Link>
                    <p className="text-muted truncate text-xs">
                        c/{community.name} · {community.members_count} members
                    </p>
                </div>
                <JoinButton
                    community={community.name}
                    isMember={community.is_member ?? false}
                    isCreator={false}
                />
            </CardBody>
        </Card>
    );
}
