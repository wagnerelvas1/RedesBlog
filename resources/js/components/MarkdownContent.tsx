import { cn } from '@/lib/utils';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { useEffect, useState } from 'react';

marked.setOptions({ gfm: true, breaks: true });

/**
 * Renders user-authored markdown. The HTML produced by `marked` is always run
 * through DOMPurify before it reaches the DOM — raw HTML is never trusted.
 *
 * DOMPurify needs a real DOM, so the sanitized HTML is only produced after the
 * component mounts. That keeps server-side rendering working (the container is
 * rendered empty on both sides, so hydration stays consistent).
 */
export function MarkdownContent({
    content,
    className,
}: {
    content: string | null;
    className?: string;
}) {
    const [html, setHtml] = useState('');

    useEffect(() => {
        if (!content) {
            setHtml('');

            return;
        }

        setHtml(
            DOMPurify.sanitize(marked.parse(content, { async: false }), {
                USE_PROFILES: { html: true },
                FORBID_TAGS: ['style', 'form', 'input', 'iframe'],
                FORBID_ATTR: ['style', 'srcset'],
            }),
        );
    }, [content]);

    if (!content) {
        return null;
    }

    return (
        <div
            className={cn(
                'text-text [&_a]:text-primary [&_blockquote]:border-border [&_blockquote]:text-muted [&_code]:bg-surface-2 [&_pre]:bg-surface-2 space-y-2 text-sm break-words [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:pl-3 [&_code]:rounded [&_code]:px-1 [&_code]:py-0.5 [&_h1]:text-lg [&_h1]:font-bold [&_h2]:text-base [&_h2]:font-bold [&_h3]:font-semibold [&_img]:max-w-full [&_img]:rounded [&_ol]:list-decimal [&_ol]:pl-5 [&_pre]:overflow-x-auto [&_pre]:rounded [&_pre]:p-3 [&_ul]:list-disc [&_ul]:pl-5',
                className,
            )}
            // Sanitized directly above; DOMPurify strips scripts and event handlers.
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
