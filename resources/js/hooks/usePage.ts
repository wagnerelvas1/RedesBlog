import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/types';

/** Typed accessor for the props shared by `HandleInertiaRequests`. */
export function useShared(): SharedProps {
    return usePage<SharedProps>().props;
}

export function useAuthUser() {
    return useShared().auth.user;
}
