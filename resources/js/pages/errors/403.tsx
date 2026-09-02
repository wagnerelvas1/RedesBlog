import { Button } from '@/components/ui/Button';
import { AppLayout } from '@/layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { home } from '@/routes';

export default function Error403() {
    return (
        <>
            <Head title="Access denied" />

            <div className="border-border bg-surface flex flex-col items-center gap-3 rounded-lg border px-6 py-16 text-center">
                <p className="text-primary text-4xl font-bold">403</p>
                <h1 className="text-text text-lg font-bold">Access denied</h1>
                <p className="text-muted max-w-sm text-sm">
                    You do not have permission to view this page.
                </p>
                <Link href={home().url} className="mt-2">
                    <Button size="sm">Back to home</Button>
                </Link>
            </div>
        </>
    );
}

Error403.layout = [AppLayout];
