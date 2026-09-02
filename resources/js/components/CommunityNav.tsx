import { Avatar } from '@/components/ui/Avatar';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { CommunitySummary, SharedProps } from '@/types';
import {
    create as communityCreate,
    index as communityIndex,
} from '@/routes/communities';
import { home } from '@/routes';
import { saved } from '@/routes/posts';

/**
 * Left sidebar: the user's communities plus the global entry points.
 *
 * `sidebar.communities` is an optional prop, so it only arrives on pages that
 * explicitly request it.
 */
export function CommunityNav({ onNavigate }: { onNavigate?: () => void }) {
    const page = usePage<SharedProps>();
    const communities: CommunitySummary[] =
        page.props.sidebar?.communities ?? [];
    const current = page.url;

    return (
        <nav aria-label="Communities" className="flex flex-col gap-6 p-4">
            <ul className="flex flex-col gap-1">
                <NavLink
                    href={home().url}
                    active={current === '/'}
                    onNavigate={onNavigate}
                >
                    🏠 Home
                </NavLink>
                <NavLink
                    href={communityIndex().url}
                    active={current.startsWith('/communities')}
                    onNavigate={onNavigate}
                >
                    🌐 Explore communities
                </NavLink>
                <NavLink
                    href={saved().url}
                    active={current.startsWith('/saved')}
                    onNavigate={onNavigate}
                >
                    🔖 Saved
                </NavLink>
            </ul>

            <div>
                <h2 className="text-muted px-3 pb-2 text-[11px] font-bold tracking-wide uppercase">
                    Your communities
                </h2>
                {communities.length === 0 ? (
                    <p className="text-muted px-3 text-sm">
                        You have not joined any community yet.
                    </p>
                ) : (
                    <ul className="flex flex-col gap-1">
                        {communities.map((community) => (
                            <li key={community.id}>
                                <Link
                                    href={`/c/${community.name}`}
                                    onClick={onNavigate}
                                    className="text-text hover:bg-surface-2 flex items-center gap-2 rounded-md px-3 py-2 text-sm transition"
                                >
                                    <Avatar
                                        src={community.avatar_url}
                                        name={community.title}
                                        size="sm"
                                    />
                                    <span className="truncate">
                                        c/{community.name}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Link
                href={communityCreate().url}
                onClick={onNavigate}
                className="text-primary hover:bg-surface-2 rounded-md px-3 py-2 text-sm font-semibold transition"
            >
                + Create a community
            </Link>
        </nav>
    );
}

function NavLink({
    href,
    active,
    children,
    onNavigate,
}: {
    href: string;
    active: boolean;
    children: React.ReactNode;
    onNavigate?: () => void;
}) {
    return (
        <li>
            <Link
                href={href}
                onClick={onNavigate}
                className={cn(
                    'block rounded-md px-3 py-2 text-sm transition',
                    active
                        ? 'bg-surface-2 text-text font-semibold'
                        : 'text-text hover:bg-surface-2',
                )}
            >
                {children}
            </Link>
        </li>
    );
}
