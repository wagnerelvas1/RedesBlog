import { Badge } from '@/components/ui/Badge';
import type { CommunityRole } from '@/types';

export function RoleBadge({
    role,
    isCreator,
    bannedAt,
}: {
    role: CommunityRole;
    isCreator: boolean;
    bannedAt: string | null;
}) {
    if (bannedAt) {
        return <Badge tone="danger">Banned</Badge>;
    }

    if (isCreator) {
        return <Badge tone="primary">Creator</Badge>;
    }

    return (
        <Badge tone={role === 'admin' ? 'success' : 'neutral'}>
            {role === 'admin' ? 'Admin' : 'Member'}
        </Badge>
    );
}
