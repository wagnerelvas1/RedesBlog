import type { Auth } from './auth';
import type { CommunitySummary } from './models';

export type Flash = {
    success: string | null;
    error: string | null;
};

export type SharedProps = {
    name: string;
    auth: Auth;
    flash: Flash;
    sidebar: {
        communities?: CommunitySummary[];
    };
    appearance: Appearance;
    [key: string]: unknown;
};

export type Appearance = 'light' | 'dark' | 'system';
