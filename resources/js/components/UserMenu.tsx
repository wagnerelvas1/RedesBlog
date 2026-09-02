import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { Dropdown, DropdownItem } from '@/components/ui/Dropdown';
import { useAuthUser } from '@/hooks/usePage';
import { Link, router } from '@inertiajs/react';
import { login, logout, register } from '@/routes';
import { saved } from '@/routes/posts';
import { edit as profileEdit } from '@/routes/profile';

export function UserMenu() {
    const user = useAuthUser();

    if (!user) {
        return (
            <div className="flex items-center gap-2">
                <Link href={login().url}>
                    <Button variant="secondary" size="sm">
                        Log in
                    </Button>
                </Link>
                <Link href={register().url} className="hidden sm:block">
                    <Button size="sm">Sign up</Button>
                </Link>
            </div>
        );
    }

    return (
        <Dropdown
            trigger={({ toggle, open }) => (
                <button
                    type="button"
                    onClick={toggle}
                    aria-haspopup="menu"
                    aria-expanded={open}
                    className="border-border hover:bg-surface-2 flex cursor-pointer items-center gap-2 rounded-full border px-2 py-1 transition"
                >
                    <Avatar src={user.avatar_url} name={user.name} size="sm" />
                    <span className="text-text hidden max-w-24 truncate text-sm font-semibold sm:block">
                        {user.username}
                    </span>
                </button>
            )}
        >
            {({ close }) => (
                <>
                    <Link
                        href={`/u/${user.username}`}
                        onClick={close}
                        className="text-text hover:bg-surface-2 block px-3 py-2 text-sm transition"
                    >
                        My profile
                    </Link>
                    <Link
                        href={saved().url}
                        onClick={close}
                        className="text-text hover:bg-surface-2 block px-3 py-2 text-sm transition"
                    >
                        Saved posts
                    </Link>
                    <Link
                        href={profileEdit().url}
                        onClick={close}
                        className="text-text hover:bg-surface-2 block px-3 py-2 text-sm transition"
                    >
                        Settings
                    </Link>
                    <div className="border-border my-1 border-t" />
                    <DropdownItem
                        onClick={() => {
                            close();
                            router.post(logout().url);
                        }}
                    >
                        Log out
                    </DropdownItem>
                </>
            )}
        </Dropdown>
    );
}
