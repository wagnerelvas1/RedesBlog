import { Flash } from '@/components/Flash';
import { ThemeToggle } from '@/components/ThemeToggle';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { home } from '@/routes';

export function AuthLayout({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <div className="bg-bg flex min-h-screen flex-col items-center justify-center gap-6 px-4 py-10">
            <div className="flex w-full max-w-sm items-center justify-between">
                <Link
                    href={home().url}
                    className="text-text flex items-center gap-2 font-bold"
                >
                    <span
                        aria-hidden="true"
                        className="bg-primary text-primary-contrast grid h-7 w-7 place-items-center rounded-full text-sm"
                    >
                        R
                    </span>
                    RedesBlog
                </Link>
                <ThemeToggle />
            </div>

            <div className="border-border bg-surface w-full max-w-sm rounded-lg border p-6">
                <h1 className="text-text text-lg font-bold">{title}</h1>
                {description ? (
                    <p className="text-muted mt-1 text-sm">{description}</p>
                ) : null}
                <div className="mt-5">{children}</div>
            </div>

            <Flash />
        </div>
    );
}
