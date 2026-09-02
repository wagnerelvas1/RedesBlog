import { useCallback, useEffect, useState } from 'react';
import type { Appearance } from '@/types';

const STORAGE_KEY = 'appearance';
const COOKIE_MAX_AGE = 31536000;

function prefersDark(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches
    );
}

function readStored(): Appearance {
    if (typeof window === 'undefined') {
        return 'system';
    }

    const stored = window.localStorage.getItem(STORAGE_KEY);

    return stored === 'light' || stored === 'dark' || stored === 'system'
        ? stored
        : 'system';
}

export function applyAppearance(appearance: Appearance): void {
    if (typeof document === 'undefined') {
        return;
    }

    const dark =
        appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', dark);
}

/**
 * Applies the stored appearance as early as possible on the client so the
 * class matches what the blade template already rendered.
 */
export function initializeAppearance(): void {
    applyAppearance(readStored());
}

export function useAppearance() {
    const [appearance, setAppearanceState] = useState<Appearance>(readStored);

    const setAppearance = useCallback((next: Appearance) => {
        setAppearanceState(next);
        window.localStorage.setItem(STORAGE_KEY, next);
        document.cookie = `${STORAGE_KEY}=${next}; path=/; max-age=${COOKIE_MAX_AGE}; SameSite=Lax`;
        applyAppearance(next);
    }, []);

    useEffect(() => {
        applyAppearance(appearance);

        if (appearance !== 'system') {
            return;
        }

        // While following the system, react to the OS switching themes.
        const query = window.matchMedia('(prefers-color-scheme: dark)');
        const onChange = () => applyAppearance('system');

        query.addEventListener('change', onChange);

        return () => query.removeEventListener('change', onChange);
    }, [appearance]);

    return { appearance, setAppearance };
}
