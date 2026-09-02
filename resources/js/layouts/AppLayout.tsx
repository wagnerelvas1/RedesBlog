import { CommunityNav } from '@/components/CommunityNav';
import { Flash } from '@/components/Flash';
import { ThemeToggle } from '@/components/ThemeToggle';
import { UserMenu } from '@/components/UserMenu';
import { Button } from '@/components/ui/Button';
import { Drawer } from '@/components/ui/Drawer';
import { Input } from '@/components/ui/Input';
import { useAuthUser } from '@/hooks/usePage';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { home } from '@/routes';
import {
    create as communityCreate,
    index as communityIndex,
} from '@/routes/communities';

/**
 * The Reddit-like frame: sticky top bar, sticky left sidebar on `lg` and up
 * (a slide-over drawer below that), a capped main column and an optional
 * right rail from `xl`.
 */
export function AppLayout({
    children,
    rightRail,
}: {
    children: ReactNode;
    rightRail?: ReactNode;
}) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [search, setSearch] = useState('');
    const user = useAuthUser();

    function submitSearch(event: React.FormEvent) {
        event.preventDefault();
        router.get(communityIndex().url, { search: search || undefined });
    }

    return (
        <div className="bg-bg min-h-screen">
            <header className="border-border bg-surface sticky top-0 z-30 border-b">
                <div className="mx-auto flex h-12 max-w-[1600px] items-center gap-3 px-3">
                    <button
                        type="button"
                        onClick={() => setDrawerOpen(true)}
                        aria-label="Open navigation"
                        className="text-muted hover:bg-surface-2 cursor-pointer rounded-md px-2 py-1 text-lg transition lg:hidden"
                    >
                        ☰
                    </button>

                    <Link
                        href={home().url}
                        className="text-text flex shrink-0 items-center gap-2 font-bold"
                    >
                        <span
                            aria-hidden="true"
                            className="bg-primary text-primary-contrast grid h-7 w-7 place-items-center rounded-full text-sm"
                        >
                            R
                        </span>
                        <span className="hidden sm:block">RedesBlog</span>
                    </Link>

                    <form
                        onSubmit={submitSearch}
                        role="search"
                        className="mx-auto w-full max-w-md"
                    >
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search communities"
                            aria-label="Search communities"
                            className="bg-surface-2 h-8 rounded-full"
                        />
                    </form>

                    <div className="flex shrink-0 items-center gap-2">
                        {user ? (
                            <Link
                                href={communityCreate().url}
                                className="hidden md:block"
                            >
                                <Button size="sm" variant="secondary">
                                    + Create
                                </Button>
                            </Link>
                        ) : null}
                        <ThemeToggle className="hidden sm:inline-flex" />
                        <UserMenu />
                    </div>
                </div>
            </header>

            <div className="mx-auto flex max-w-[1600px] gap-6 px-3 py-4">
                <aside className="border-border bg-surface sticky top-16 hidden h-[calc(100vh-5rem)] w-64 shrink-0 overflow-y-auto rounded-lg border lg:block">
                    <CommunityNav />
                </aside>

                <main className="mx-auto w-full max-w-[640px] min-w-0">
                    {children}
                </main>

                {rightRail ? (
                    <aside className="sticky top-16 hidden h-fit w-80 shrink-0 xl:block">
                        {rightRail}
                    </aside>
                ) : null}
            </div>

            <Drawer open={drawerOpen} onClose={() => setDrawerOpen(false)}>
                <CommunityNav onNavigate={() => setDrawerOpen(false)} />
                <div className="border-border border-t p-4">
                    <ThemeToggle />
                </div>
            </Drawer>

            <Flash />
        </div>
    );
}

/** Helper for Inertia persistent layouts. */
export function withAppLayout(page: ReactNode, rightRail?: ReactNode) {
    return <AppLayout rightRail={rightRail}>{page}</AppLayout>;
}
