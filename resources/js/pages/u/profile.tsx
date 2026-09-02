import { RelativeTime } from '@/components/RelativeTime';
import { Avatar } from '@/components/ui/Avatar';
import { Card, CardBody } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Tabs } from '@/components/ui/Tabs';
import { AppLayout } from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

type Props = {
    profile: {
        id: number;
        name: string;
        username: string;
        bio: string | null;
        avatar_url: string | null;
        created_at: string | null;
    };
};

const TABS = [
    { value: 'posts' as const, label: 'Posts' },
    { value: 'comments' as const, label: 'Comments' },
];

export default function UserProfile({ profile }: Props) {
    const [tab, setTab] = useState<'posts' | 'comments'>('posts');

    return (
        <>
            <Head title={`u/${profile.username}`} />

            <Card className="mb-4">
                <CardBody className="flex flex-wrap items-center gap-4">
                    <Avatar
                        src={profile.avatar_url}
                        name={profile.name}
                        size="xl"
                    />
                    <div className="min-w-0 flex-1">
                        <h1 className="text-text truncate text-xl font-bold">
                            u/{profile.username}
                        </h1>
                        <p className="text-muted text-sm">{profile.name}</p>
                        {profile.created_at ? (
                            <p className="text-muted mt-1 text-xs">
                                Joined{' '}
                                <RelativeTime value={profile.created_at} />
                            </p>
                        ) : null}
                    </div>
                </CardBody>
            </Card>

            {profile.bio ? (
                <Card className="mb-4">
                    <CardBody>
                        <p className="text-text text-sm">{profile.bio}</p>
                    </CardBody>
                </Card>
            ) : null}

            <div className="mb-3">
                <Tabs items={TABS} value={tab} onChange={setTab} />
            </div>

            <EmptyState
                icon={tab === 'posts' ? '📝' : '💬'}
                title={`No ${tab} to show`}
                description={`u/${profile.username} has not published any ${tab} yet.`}
            />
        </>
    );
}

UserProfile.layout = [AppLayout];
