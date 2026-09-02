import { Toast } from '@/components/ui/Toast';
import { useShared } from '@/hooks/usePage';
import { useEffect, useState } from 'react';

type Entry = { id: number; message: string; tone: 'success' | 'error' };

let nextId = 0;

/** Turns the shared `flash` props into transient toasts. */
export function Flash() {
    const { flash } = useShared();
    const [entries, setEntries] = useState<Entry[]>([]);

    useEffect(() => {
        const incoming: Entry[] = [];

        if (flash.success) {
            incoming.push({
                id: nextId++,
                message: flash.success,
                tone: 'success',
            });
        }

        if (flash.error) {
            incoming.push({
                id: nextId++,
                message: flash.error,
                tone: 'error',
            });
        }

        if (incoming.length > 0) {
            setEntries((current) => [...current, ...incoming]);
        }
    }, [flash.success, flash.error]);

    if (entries.length === 0) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4">
            {entries.map((entry) => (
                <Toast
                    key={entry.id}
                    message={entry.message}
                    tone={entry.tone}
                    onDismiss={() =>
                        setEntries((current) =>
                            current.filter((item) => item.id !== entry.id),
                        )
                    }
                />
            ))}
        </div>
    );
}
