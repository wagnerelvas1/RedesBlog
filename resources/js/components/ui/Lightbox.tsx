import { useEffect } from 'react';

export type LightboxProps = {
    src: string | null;
    alt: string;
    onClose: () => void;
};

export function Lightbox({ src, alt, onClose }: LightboxProps) {
    useEffect(() => {
        if (!src) {
            return;
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose();
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [src, onClose]);

    if (!src) {
        return null;
    }

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={alt}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4"
            onClick={onClose}
        >
            <img
                src={src}
                alt={alt}
                className="max-h-full max-w-full rounded object-contain"
            />
            <button
                type="button"
                onClick={onClose}
                aria-label="Close image"
                className="absolute top-4 right-4 cursor-pointer rounded-full bg-white/10 px-3 py-1 text-lg text-white"
            >
                ×
            </button>
        </div>
    );
}
