import { Button } from '@/components/ui/Button';
import { useAuthUser } from '@/hooks/usePage';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { join, leave } from '@/routes/communities';
import { login } from '@/routes';

/** Optimistic join/leave toggle, reverted if the request fails. */
export function JoinButton({
    community,
    isMember,
    isCreator,
    className,
}: {
    community: string;
    isMember: boolean;
    isCreator: boolean;
    className?: string;
}) {
    const user = useAuthUser();
    const [member, setMember] = useState(isMember);

    if (isCreator) {
        return (
            <Button
                variant="secondary"
                size="sm"
                disabled
                className={className}
            >
                Creator
            </Button>
        );
    }

    function toggle() {
        if (!user) {
            router.visit(login().url);

            return;
        }

        const next = !member;
        setMember(next);

        const endpoint = next ? join(community) : leave(community);

        router.visit(endpoint.url, {
            method: endpoint.method,
            preserveScroll: true,
            onError: () => setMember(!next),
        });
    }

    return (
        <Button
            variant={member ? 'secondary' : 'primary'}
            size="sm"
            onClick={toggle}
            className={className}
        >
            {member ? 'Joined' : 'Join'}
        </Button>
    );
}
