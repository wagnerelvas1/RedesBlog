import { Button } from '@/components/ui/Button';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { home } from '@/routes';

export default function Error404() {
    return (
        <>
            <Head title="Page not found" />

            <div className="border-border bg-surface flex flex-col items-center gap-3 rounded-lg border px-6 py-16 text-center">
                <p className="text-primary text-4xl font-bold">404</p>
                <h1 className="text-text text-lg font-bold">Page not found</h1>
                <p className="text-muted max-w-sm text-sm">
                    The page you are looking for does not exist or was removed.
                </p>
                <Link href={home().url} className="mt-2">
                    <Button size="sm">Back to home</Button>
                </Link>
            </div>
        </>
    );
}

Error404.layout = [AppLayout];
