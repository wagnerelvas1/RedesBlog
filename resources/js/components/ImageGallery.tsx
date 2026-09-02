import { Lightbox } from '@/components/ui/Lightbox';
import { cn } from '@/lib/utils';
import { useState } from 'react';
import type { Attachment } from '@/types';

export function ImageGallery({
    attachments,
    className,
}: {
    attachments: Attachment[];
    className?: string;
}) {
    const [active, setActive] = useState<Attachment | null>(null);

    if (attachments.length === 0) {
        return null;
    }

    return (
        <>
            <div
                className={cn(
                    'grid gap-2',
                    attachments.length > 1 ? 'grid-cols-2' : 'grid-cols-1',
                    className,
                )}
            >
                {attachments.map((attachment) => (
                    <button
                        key={attachment.id}
                        type="button"
                        onClick={() => setActive(attachment)}
                        className="border-border bg-surface-2 cursor-zoom-in overflow-hidden rounded-lg border"
                    >
                        <img
                            src={attachment.url}
                            alt={attachment.original_name}
                            loading="lazy"
                            width={attachment.width ?? undefined}
                            height={attachment.height ?? undefined}
                            className="h-full max-h-[512px] w-full object-cover"
                        />
                    </button>
                ))}
            </div>

            <Lightbox
                src={active?.url ?? null}
                alt={active?.original_name ?? 'Image'}
                onClose={() => setActive(null)}
            />
        </>
    );
}
