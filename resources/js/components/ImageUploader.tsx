import { Button } from '@/components/ui/Button';
import { cn } from '@/lib/utils';
import { useEffect, useRef, useState } from 'react';

const MAX_BYTES = 5 * 1024 * 1024;
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

export type ExistingImage = { id: number; url: string; name: string };

export type ImageUploaderProps = {
    files: File[];
    onFilesChange: (files: File[]) => void;
    existing?: ExistingImage[];
    keptIds?: number[];
    onKeptIdsChange?: (ids: number[]) => void;
    max?: number;
    label?: string;
    error?: string;
};

/**
 * Drag-and-drop image picker with previews.
 *
 * Mirrors the server rules (type, 5 MB, count) so obvious mistakes are caught
 * before the upload starts; the backend remains the source of truth.
 */
export function ImageUploader({
    files,
    onFilesChange,
    existing = [],
    keptIds = [],
    onKeptIdsChange,
    max = 10,
    label = 'Images',
    error,
}: ImageUploaderProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const [previews, setPreviews] = useState<string[]>([]);

    useEffect(() => {
        const urls = files.map((file) => URL.createObjectURL(file));
        setPreviews(urls);

        return () => urls.forEach((url) => URL.revokeObjectURL(url));
    }, [files]);

    const keptExisting = existing.filter((image) => keptIds.includes(image.id));
    const total = keptExisting.length + files.length;

    function addFiles(incoming: FileList | null) {
        if (!incoming) {
            return;
        }

        const accepted: File[] = [];
        let message: string | null = null;

        for (const file of Array.from(incoming)) {
            if (!ACCEPTED.includes(file.type)) {
                message = 'Only JPG, PNG, WebP and GIF images are accepted.';
                continue;
            }

            if (file.size > MAX_BYTES) {
                message = 'Each image must be 5 MB or smaller.';
                continue;
            }

            accepted.push(file);
        }

        const room = max - total;

        if (accepted.length > room) {
            message = `You can attach at most ${max} image${max === 1 ? '' : 's'}.`;
        }

        setLocalError(message);
        onFilesChange([...files, ...accepted.slice(0, Math.max(room, 0))]);
    }

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <span className="text-text text-sm font-medium">{label}</span>
                <span className="text-muted text-xs">
                    {total}/{max}
                </span>
            </div>

            <div
                onDragOver={(event) => {
                    event.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={(event) => {
                    event.preventDefault();
                    setDragging(false);
                    addFiles(event.dataTransfer.files);
                }}
                className={cn(
                    'rounded-lg border border-dashed px-4 py-6 text-center transition',
                    dragging ? 'border-primary bg-surface-2' : 'border-border',
                )}
            >
                <p className="text-muted text-sm">Drag images here, or</p>
                <Button
                    variant="secondary"
                    size="sm"
                    className="mt-2"
                    disabled={total >= max}
                    onClick={() => inputRef.current?.click()}
                >
                    Choose files
                </Button>
                <input
                    ref={inputRef}
                    type="file"
                    multiple={max > 1}
                    accept={ACCEPTED.join(',')}
                    className="hidden"
                    onChange={(event) => {
                        addFiles(event.target.files);
                        event.target.value = '';
                    }}
                />
            </div>

            {localError || error ? (
                <p role="alert" className="text-sm text-red-500">
                    {localError ?? error}
                </p>
            ) : null}

            {keptExisting.length > 0 || files.length > 0 ? (
                <ul className="grid grid-cols-3 gap-2 sm:grid-cols-4">
                    {keptExisting.map((image) => (
                        <li key={`existing-${image.id}`} className="relative">
                            <img
                                src={image.url}
                                alt={image.name}
                                className="border-border h-24 w-full rounded border object-cover"
                            />
                            <RemoveButton
                                label={`Remove ${image.name}`}
                                onClick={() =>
                                    onKeptIdsChange?.(
                                        keptIds.filter((id) => id !== image.id),
                                    )
                                }
                            />
                        </li>
                    ))}
                    {files.map((file, index) => (
                        <li
                            key={`new-${file.name}-${index}`}
                            className="relative"
                        >
                            <img
                                src={previews[index]}
                                alt={file.name}
                                className="border-border h-24 w-full rounded border object-cover"
                            />
                            <RemoveButton
                                label={`Remove ${file.name}`}
                                onClick={() =>
                                    onFilesChange(
                                        files.filter((_, i) => i !== index),
                                    )
                                }
                            />
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}

function RemoveButton({
    label,
    onClick,
}: {
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            className="absolute top-1 right-1 cursor-pointer rounded-full bg-black/70 px-1.5 text-xs text-white"
        >
            ×
        </button>
    );
}
