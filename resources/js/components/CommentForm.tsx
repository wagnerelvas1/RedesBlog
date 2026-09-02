import { Button } from '@/components/ui/Button';
import { Textarea } from '@/components/ui/Textarea';
import { router } from '@inertiajs/react';
import type { FormDataConvertible } from '@inertiajs/core';
import { useState } from 'react';
import {
    store as commentStore,
    update as commentUpdate,
} from '@/routes/comments';

const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const MAX_BYTES = 5 * 1024 * 1024;

export type CommentFormProps = {
    community: string;
    postId: number;
    parentId?: number | null;
    commentId?: number;
    initialBody?: string;
    submitLabel?: string;
    autoFocus?: boolean;
    onDone?: () => void;
    onCancel?: () => void;
};

/**
 * Shared by the top-level composer, the reply box and the edit box.
 * A comment needs a body, an image, or both.
 */
export function CommentForm({
    community,
    postId,
    parentId = null,
    commentId,
    initialBody = '',
    submitLabel = 'Comment',
    autoFocus = false,
    onDone,
    onCancel,
}: CommentFormProps) {
    const [body, setBody] = useState(initialBody);
    const [image, setImage] = useState<File | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const editing = commentId !== undefined;

    function submit(event: React.FormEvent) {
        event.preventDefault();

        if (body.trim() === '' && !image) {
            setError('Write something or attach an image.');

            return;
        }

        const endpoint = editing
            ? commentUpdate([community, postId, commentId])
            : commentStore([community, postId]);

        const data: Record<string, FormDataConvertible> = { body };

        if (parentId !== null) {
            data.parent_id = parentId;
        }

        if (image) {
            data.image = image;
        }

        if (editing) {
            // Laravel needs the spoofed method when the payload carries a file.
            data._method = 'patch';
        }

        setProcessing(true);

        router.post(endpoint.url, data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setBody(editing ? body : '');
                setImage(null);
                setError(null);
                onDone?.();
            },
            onError: (errors) =>
                setError(Object.values(errors)[0] ?? 'Something went wrong.'),
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <form onSubmit={submit} className="space-y-2">
            <Textarea
                value={body}
                rows={4}
                autoFocus={autoFocus}
                onChange={(event) => setBody(event.target.value)}
                placeholder="What are your thoughts?"
                invalid={error !== null}
            />

            {error ? (
                <p role="alert" className="text-sm text-red-500">
                    {error}
                </p>
            ) : null}

            <div className="flex flex-wrap items-center gap-2">
                <label className="text-muted hover:text-text cursor-pointer text-xs font-semibold">
                    <input
                        type="file"
                        accept={ACCEPTED.join(',')}
                        className="hidden"
                        onChange={(event) => {
                            const file = event.target.files?.[0] ?? null;

                            if (file && file.size > MAX_BYTES) {
                                setError('The image must be 5 MB or smaller.');

                                return;
                            }

                            setError(null);
                            setImage(file);
                        }}
                    />
                    📎 {image ? image.name : 'Attach image'}
                </label>

                {image ? (
                    <button
                        type="button"
                        onClick={() => setImage(null)}
                        className="cursor-pointer text-xs text-red-500"
                    >
                        Remove
                    </button>
                ) : null}

                <div className="ml-auto flex gap-2">
                    {onCancel ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={onCancel}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                    ) : null}
                    <Button type="submit" size="sm" disabled={processing}>
                        {processing ? 'Saving…' : submitLabel}
                    </Button>
                </div>
            </div>
        </form>
    );
}
