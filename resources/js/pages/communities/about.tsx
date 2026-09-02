import { CommunityHeader } from '@/components/CommunityHeader';
import { MarkdownContent } from '@/components/MarkdownContent';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { AppLayout } from '@/layouts/AppLayout';
import { Head } from '@inertiajs/react';
import type { Community, CommunityPermissions, Membership } from '@/types';

type Props = {
    community: Community;
    membership: Membership;
    permissions: CommunityPermissions;
};

export default function CommunityAbout({
    community,
    membership,
    permissions,
}: Props) {
    return (
        <>
            <Head title={`About c/${community.name}`} />

            <CommunityHeader
                community={community}
                membership={membership}
                permissions={permissions}
            />

            <Card className="mb-4">
                <CardHeader>
                    <h1 className="text-text text-sm font-bold">Description</h1>
                </CardHeader>
                <CardBody>
                    {community.description ? (
                        <p className="text-text text-sm">
                            {community.description}
                        </p>
                    ) : (
                        <p className="text-muted text-sm italic">
                            This community has no description yet.
                        </p>
                    )}
                </CardBody>
            </Card>

            <Card>
                <CardHeader>
                    <h2 className="text-text text-sm font-bold">Rules</h2>
                </CardHeader>
                <CardBody>
                    {community.rules ? (
                        <MarkdownContent content={community.rules} />
                    ) : (
                        <p className="text-muted text-sm italic">
                            No rules have been published.
                        </p>
                    )}
                </CardBody>
            </Card>
        </>
    );
}

CommunityAbout.layout = [AppLayout];
