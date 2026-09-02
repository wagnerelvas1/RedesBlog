import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { useEffect, useRef, useState } from 'react';

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const MAX_BYTES = 5 * 1024 * 1024;

export function AvatarUploader({
    currentUrl,
    name,
    file,
    onFileChange,
    onRemove,
    removed,
}: {
    currentUrl: string | null;
    name: string;
    file: File | null;
    onFileChange: (file: File | null) => void;
    onRemove: () => void;
    removed: boolean;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(file);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [file]);

    const shown = preview ?? (removed ? null : currentUrl);

    return (
        <div className="flex items-center gap-4">
            <Avatar src={shown} name={name} size="xl" />

            <div className="space-y-2">
                <div className="flex gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => inputRef.current?.click()}
                    >
                        Choose image
                    </Button>
                    {shown ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                onFileChange(null);
                                onRemove();
                            }}
                        >
                            Remove
                        </Button>
                    ) : null}
                </div>
                <p className="text-muted text-xs">
                    JPG, PNG, WebP or GIF. Up to 5 MB.
                </p>
                {error ? (
                    <p role="alert" className="text-xs text-red-500">
                        {error}
                    </p>
                ) : null}
            </div>

            <input
                ref={inputRef}
                type="file"
                accept={ACCEPTED.join(',')}
                className="hidden"
                onChange={(event) => {
                    const chosen = event.target.files?.[0] ?? null;
                    event.target.value = '';

                    if (chosen && !ACCEPTED.includes(chosen.type)) {
                        setError('Only JPG, PNG, WebP and GIF are accepted.');

                        return;
                    }

                    if (chosen && chosen.size > MAX_BYTES) {
                        setError('The image must be 5 MB or smaller.');

                        return;
                    }

                    setError(null);
                    onFileChange(chosen);
                }}
            />
        </div>
    );
}
