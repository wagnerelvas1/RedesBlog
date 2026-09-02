import { Button } from '@/components/ui/Button';
import { useAuthUser } from '@/hooks/usePage';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { login } from '@/routes';

/**
 * Renders write affordances only for authenticated users; guests get a prompt
 * that carries them to the login screen and back.
 */
export function GuestGate({
    children,
    message = 'Log in to continue.',
}: {
    children: ReactNode;
    message?: string;
}) {
    const user = useAuthUser();

    if (user) {
        return <>{children}</>;
    }

    return (
        <div className="border-border bg-surface text-muted flex flex-wrap items-center gap-3 rounded-lg border border-dashed px-4 py-3 text-sm">
            <span className="flex-1">{message}</span>
            <Link href={login().url}>
                <Button size="sm">Log in</Button>
            </Link>
        </div>
    );
}
