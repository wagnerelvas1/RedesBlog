import { JoinButton } from '@/components/JoinButton';
import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { Link } from '@inertiajs/react';
import type { Community, CommunityPermissions, Membership } from '@/types';
import { edit as communitySettingsEdit } from '@/routes/communities/settings';
import { create as postCreate } from '@/routes/posts';

export function CommunityHeader({
    community,
    membership,
    permissions,
}: {
    community: Community;
    membership: Membership;
    permissions: CommunityPermissions;
}) {
    return (
        <header className="border-border bg-surface mb-4 overflow-hidden rounded-lg border">
            <div className="bg-surface-2 h-24 w-full sm:h-32">
                {community.banner_url ? (
                    <img
                        src={community.banner_url}
                        alt=""
                        className="h-full w-full object-cover"
                    />
                ) : null}
            </div>

            <div className="flex flex-wrap items-end gap-3 px-4 pb-4">
                <Avatar
                    src={community.avatar_url}
                    name={community.title}
                    size="xl"
                    className="border-surface -mt-8 border-4"
                />

                <div className="min-w-0 flex-1">
                    <h1 className="text-text truncate text-xl font-bold">
                        {community.title}
                    </h1>
                    <p className="text-muted text-sm">
                        c/{community.name} · {community.members_count} members
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <JoinButton
                        community={community.name}
                        isMember={membership.is_member}
                        isCreator={membership.is_creator}
                    />
                    {permissions.can_post ? (
                        <Link href={postCreate(community.name).url}>
                            <Button size="sm">Create post</Button>
                        </Link>
                    ) : null}
                    {permissions.can_update ? (
                        <Link
                            href={communitySettingsEdit(community.name).url}
                            aria-label="Community settings"
                        >
                            <Button variant="ghost" size="icon">
                                ⚙
                            </Button>
                        </Link>
                    ) : null}
                </div>
            </div>
        </header>
    );
}
