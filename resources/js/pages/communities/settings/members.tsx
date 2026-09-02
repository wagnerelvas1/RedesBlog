import { RoleBadge } from '@/components/RoleBadge';
import { RelativeTime } from '@/components/RelativeTime';
import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Input } from '@/components/ui/Input';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import type { FormDataConvertible } from '@inertiajs/core';
import { useState } from 'react';
import type { Community, Member, Paginated } from '@/types';
import {
    destroy as memberDestroy,
    index as membersIndex,
    update as memberUpdate,
} from '@/routes/communities/members';
import { edit as settingsEdit } from '@/routes/communities/settings';

type Props = {
    community: Community;
    members: Paginated<Member>;
    filters: { search: string | null; role: string | null };
    permissions: { can_manage_admins: boolean };
};

export default function CommunityMembers({
    community,
    members,
    filters,
    permissions,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function patch(
        member: Member,
        payload: Record<string, FormDataConvertible>,
    ) {
        router.patch(
            memberUpdate([community.name, member.username]).url,
            payload,
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`Members · c/${community.name}`} />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <h1 className="text-text mr-auto text-xl font-bold">
                    Members of c/{community.name}
                </h1>
                <Link
                    href={settingsEdit(community.name).url}
                    className="text-primary text-sm font-semibold hover:underline"
                >
                    ← Back to settings
                </Link>
            </div>

            <form
                className="mb-4 flex gap-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get(
                        membersIndex(community.name).url,
                        { search: search || undefined },
                        { preserveState: true, replace: true },
                    );
                }}
            >
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search members"
                    aria-label="Search members"
                />
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </form>

            {members.data.length === 0 ? (
                <EmptyState icon="👥" title="No members found" />
            ) : (
                <Card>
                    <CardBody className="p-0">
                        <ul className="divide-border divide-y">
                            {members.data.map((member) => (
                                <li
                                    key={member.id}
                                    className="flex flex-wrap items-center gap-3 px-4 py-3"
                                >
                                    <Avatar
                                        src={member.avatar_url}
                                        name={member.name}
                                        size="md"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <Link
                                            href={`/u/${member.username}`}
                                            className="text-text block truncate text-sm font-semibold hover:underline"
                                        >
                                            u/{member.username}
                                        </Link>
                                        {member.joined_at ? (
                                            <p className="text-muted text-xs">
                                                Joined{' '}
                                                <RelativeTime
                                                    value={member.joined_at}
                                                />
                                            </p>
                                        ) : null}
                                    </div>

                                    <RoleBadge
                                        role={member.role}
                                        isCreator={member.is_creator}
                                        bannedAt={member.banned_at}
                                    />

                                    {member.is_creator ? null : (
                                        <div className="flex flex-wrap gap-1">
                                            {permissions.can_manage_admins ? (
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() =>
                                                        patch(member, {
                                                            role:
                                                                member.role ===
                                                                'admin'
                                                                    ? 'member'
                                                                    : 'admin',
                                                        })
                                                    }
                                                >
                                                    {member.role === 'admin'
                                                        ? 'Demote'
                                                        : 'Promote'}
                                                </Button>
                                            ) : null}

                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                onClick={() =>
                                                    patch(member, {
                                                        banned: !member.banned_at,
                                                    })
                                                }
                                            >
                                                {member.banned_at
                                                    ? 'Unban'
                                                    : 'Ban'}
                                            </Button>

                                            <Button
                                                variant="danger"
                                                size="sm"
                                                onClick={() => {
                                                    if (
                                                        window.confirm(
                                                            `Remove u/${member.username}?`,
                                                        )
                                                    ) {
                                                        router.delete(
                                                            memberDestroy([
                                                                community.name,
                                                                member.username,
                                                            ]).url,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }
                                                }}
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </CardBody>
                </Card>
            )}
        </>
    );
}

CommunityMembers.layout = [AppLayout];
